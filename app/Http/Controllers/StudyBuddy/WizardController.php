<?php

namespace App\Http\Controllers\StudyBuddy;

use App\Http\Controllers\Controller;
use App\Models\GuestApplication;
use App\Models\StudyBuddyResponse;
use App\Services\StudyBuddy\RecommendationEngine;
use App\Services\StudyBuddy\WizardSchema;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Discovery Wizard — Aday öğrenci için akıllı program keşif sihirbazı (Faz 2).
 *
 * Akış:
 *   GET  /study-buddy           → landing
 *   GET  /study-buddy/start     → yeni session, step 1'e yönlendir
 *   GET  /study-buddy/step/{n}  → adım göster (cevap varsa pre-fill)
 *   POST /study-buddy/step/{n}  → cevabı kaydet, sonraki adıma git
 *   GET  /study-buddy/result    → öneri listesi
 *   POST /study-buddy/convert   → guest_application'a dönüştür + form'a yönlendir
 */
class WizardController extends Controller
{
    public function __construct(
        private readonly WizardSchema $schema,
        private readonly RecommendationEngine $engine,
    ) {}

    public function landing(Request $request): View
    {
        return view('study-buddy.landing');
    }

    public function start(Request $request): RedirectResponse
    {
        $companyId = (int) ($request->attributes->get('company_id') ?? 1);
        $token = (string) Str::uuid();

        StudyBuddyResponse::query()->create([
            'company_id'     => $companyId,
            'session_token'  => $token,
            'answers'        => [],
            'current_step'   => 1,
            'total_steps'    => $this->schema->totalSteps(),
            'started_at'     => now(),
            'last_active_at' => now(),
            'source'         => substr((string) $request->query('utm_source', ''), 0, 60) ?: null,
            'referrer'       => substr((string) $request->headers->get('referer', ''), 0, 500) ?: null,
            'ip'             => $request->ip(),
            'user_agent'     => substr((string) $request->headers->get('user-agent', ''), 0, 500) ?: null,
        ]);

        // Session token'ı cookie'ye yaz (3 ay) — kullanıcı yarıda bıraktıysa devam edebilir
        Cookie::queue('study_buddy_session', $token, 60 * 24 * 90);

        return redirect()->route('study-buddy.step', ['n' => 1, 't' => $token]);
    }

    public function step(Request $request, int $n): View|RedirectResponse
    {
        $response = $this->resolveSession($request);
        if (! $response) return redirect()->route('study-buddy.start');

        $total = $this->schema->totalSteps();
        $n = max(1, min($total, $n));

        // Bu step'in tanımı
        $stepDef = $this->schema->stepAt($n);
        if (! $stepDef) return redirect()->route('study-buddy.start');

        return view('study-buddy.step', [
            'response'   => $response,
            'stepDef'    => $stepDef,
            'currentStep'=> $n,
            'totalSteps' => $total,
            'progress'   => (int) round(($n / $total) * 100),
            'answer'     => $response->getAnswer($stepDef['key']),
        ]);
    }

    public function saveStep(Request $request, int $n): RedirectResponse
    {
        $response = $this->resolveSession($request);
        if (! $response) return redirect()->route('study-buddy.start');

        $total = $this->schema->totalSteps();
        $stepDef = $this->schema->stepAt($n);
        if (! $stepDef) return redirect()->route('study-buddy.start');

        $key = $stepDef['key'];
        $type = $stepDef['type'] ?? 'cards';
        $rules = $stepDef['validation'] ?? ['nullable', 'string', 'max:500'];

        // checkbox_group için item-level validation + max enforcement
        if ($type === 'checkbox_group') {
            $validRules = [$key => $rules, $key . '.*' => ['string', 'max:120']];
            $data = $request->validate($validRules);
            $value = array_values((array) ($data[$key] ?? []));

            // Max enforcement (server-side)
            $max = (int) ($stepDef['max'] ?? 0);
            if ($max > 0 && count($value) > $max) {
                $value = array_slice($value, 0, $max);
            }

            // Sadece options içindeki valid value'ları kabul et
            $allowed = array_column($stepDef['options'] ?? [], 'value');
            $value = array_values(array_intersect($value, $allowed));
        } else {
            $data = $request->validate([$key => $rules]);
            $value = $data[$key] ?? null;
        }

        $response->setAnswer($key, $value);
        $response->current_step = max($response->current_step, $n + 1);
        $response->save();

        if ($n >= $total) {
            return redirect()->route('study-buddy.complete');
        }
        return redirect()->route('study-buddy.step', ['n' => $n + 1]);
    }

    public function complete(Request $request): RedirectResponse
    {
        $response = $this->resolveSession($request);
        if (! $response) return redirect()->route('study-buddy.start');

        // Recommendation engine çalıştır
        $recs = $this->engine->recommend($response, 10);

        $response->recommendations = $recs;
        $response->completed_at = now();
        $response->last_active_at = now();
        $response->save();

        return redirect()->route('study-buddy.result');
    }

    public function result(Request $request): View|RedirectResponse
    {
        $response = $this->resolveSession($request);
        if (! $response || ! $response->isCompleted()) {
            return redirect()->route('study-buddy.start');
        }

        return view('study-buddy.result', [
            'response'        => $response,
            'recommendations' => $response->recommendations ?? [],
        ]);
    }

    /**
     * Wizard cevaplarını guest_application'a aktar.
     * Form pre-fill ile MentorDE kayıt akışına yönlendirir.
     */
    public function convert(Request $request): RedirectResponse
    {
        $response = $this->resolveSession($request);
        if (! $response || ! $response->isCompleted()) {
            return redirect()->route('study-buddy.start');
        }

        if ($response->converted_to_guest_id) {
            // Daha önce dönüşmüş — formuna yönlendir
            return redirect('/guest/registration')->with('info', 'Daha önce kayıt başlatmıştın, kaldığın yerden devam ediyorsun.');
        }

        $a = is_array($response->answers) ? $response->answers : [];
        $cities = is_array($a['preferred_cities'] ?? null) ? $a['preferred_cities'] : [];

        // Wizard cevapları → registration_form_draft mapping (15 alan)
        $draft = [
            'application_country' => 'de',
            'application_type'    => $this->mapDegreeToApplicationType($a['target_degree'] ?? null),
            'application_city'    => $cities[0] ?? null, // İlk tercih şehir
            'german_level'        => $a['german_level'] ?? null,
            'english_level'       => $a['english_level'] ?? null,
            'finance_method'      => $a['finance_method'] ?? null,
            'high_school_type'    => $a['high_school_type'] ?? null,
            'high_school_grade'   => $this->mapGpaRangeToGrade($a['gpa_range'] ?? null),
            'higher_education_status' => $this->mapEducationLevelToStatus($a['current_education_level'] ?? null),
            // Wizard'a özel: meta wizard.* alanlarına saklanabilir (ileride)
        ];
        $draft = array_filter($draft, fn ($v) => $v !== null && $v !== '');

        // Cevapların TAMAMI'nı meta'ya yedekle — manager tüm wizard cevaplarına ulaşabilsin
        $meta = ['wizard' => $a];

        $guest = GuestApplication::query()->create([
            'company_id'              => $response->company_id,
            'tracking_token'          => (string) Str::uuid(),
            'application_country'     => 'de',
            'application_type'        => $this->mapDegreeToApplicationType($a['target_degree'] ?? null),
            'registration_form_draft' => $draft,
            'application_meta'        => $meta,
            'first_name'              => null,
            'last_name'               => null,
            'email'                   => null,
        ]);

        $response->converted_to_guest_id = $guest->id;
        $response->converted_at = now();
        $response->save();

        return redirect('/guest/registration')->with('success', 'Wizard cevapların form\'a aktarıldı — sadece kalan bilgileri tamamla.');
    }

    /** Wizard target_degree → form application_type. */
    private function mapDegreeToApplicationType(?string $degree): ?string
    {
        return match ($degree) {
            'bachelor', 'master', 'phd' => $degree,
            'studienkolleg' => 'language_course', // Studienkolleg ≈ hazırlık
            default => null,
        };
    }

    /** Wizard gpa_range → 100 üzerinden ortalama not (yaklaşık merkez). */
    private function mapGpaRangeToGrade(?string $range): ?string
    {
        return match ($range) {
            'excellent' => '95',
            'very_good' => '85',
            'good'      => '75',
            'medium'    => '65',
            'low'       => '55',
            default     => null,
        };
    }

    /** Wizard current_education_level → form higher_education_status. */
    private function mapEducationLevelToStatus(?string $level): ?string
    {
        return match ($level) {
            'high_school_student', 'high_school_graduate' => 'not_started',
            'bachelor_student', 'master_student'          => 'enrolled',
            'bachelor_graduate', 'master_graduate'        => 'graduated',
            default => null,
        };
    }

    /** Session token cookie veya URL'den, sonra DB lookup. */
    private function resolveSession(Request $request): ?StudyBuddyResponse
    {
        $token = (string) ($request->query('t') ?: $request->cookie('study_buddy_session', ''));
        if ($token === '') return null;

        $companyId = (int) ($request->attributes->get('company_id') ?? 1);
        return StudyBuddyResponse::query()
            ->where('session_token', $token)
            ->where('company_id', $companyId)
            ->first();
    }
}
