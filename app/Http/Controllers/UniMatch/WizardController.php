<?php

namespace App\Http\Controllers\UniMatch;

use App\Http\Controllers\Controller;
use App\Models\GuestApplication;
use App\Models\UniMatchResponse;
use App\Services\UniMatch\RecommendationEngine;
use App\Services\UniMatch\WizardSchema;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Discovery Wizard — Aday öğrenci için akıllı program keşif sihirbazı (Faz 2).
 *
 * Akış:
 *   GET  /uni-match           → landing
 *   GET  /uni-match/start     → yeni session, step 1'e yönlendir
 *   GET  /uni-match/step/{n}  → adım göster (cevap varsa pre-fill)
 *   POST /uni-match/step/{n}  → cevabı kaydet, sonraki adıma git
 *   GET  /uni-match/result    → öneri listesi
 *   POST /uni-match/convert   → guest_application'a dönüştür + form'a yönlendir
 */
class WizardController extends Controller
{
    public function __construct(
        private readonly WizardSchema $schema,
        private readonly RecommendationEngine $engine,
    ) {}

    public function landing(Request $request): View
    {
        // Cookie'de oturum varsa yarıda kalan wizard'a devam linki göster
        $resumeData = null;
        $token = (string) $request->cookie('uni_match_session', '');
        if ($token !== '') {
            $companyId = (int) ($request->attributes->get('company_id') ?? 1);
            $existing = UniMatchResponse::query()
                ->where('session_token', $token)
                ->where('company_id', $companyId)
                ->whereNull('completed_at')
                ->where('current_step', '>', 1)
                ->first();
            if ($existing) {
                $resumeData = [
                    'step'         => $existing->current_step,
                    'progress_pct' => (int) round(($existing->current_step / 19) * 100),
                    'started_at'   => $existing->started_at?->diffForHumans(),
                ];
            }
        }

        return view('uni-match.landing', ['resume' => $resumeData]);
    }

    public function start(Request $request): RedirectResponse
    {
        $companyId = (int) ($request->attributes->get('company_id') ?? 1);
        $token = (string) Str::uuid();

        UniMatchResponse::query()->create([
            'company_id'     => $companyId,
            'session_token'  => $token,
            'answers'        => [],
            'current_step'   => 1,
            'total_steps'    => $this->schema->totalSteps(),
            'started_at'     => now(),
            'last_active_at' => now(),
            'source'         => substr((string) $request->query('utm_source', ''), 0, 60) ?: null,
            'utm_medium'     => substr((string) $request->query('utm_medium', ''), 0, 100) ?: null,
            'utm_campaign'   => substr((string) $request->query('utm_campaign', ''), 0, 100) ?: null,
            'utm_content'    => substr((string) $request->query('utm_content', ''), 0, 100) ?: null,
            'utm_term'       => substr((string) $request->query('utm_term', ''), 0, 100) ?: null,
            'referrer'       => substr((string) $request->headers->get('referer', ''), 0, 500) ?: null,
            'ip'             => $request->ip(),
            'user_agent'     => substr((string) $request->headers->get('user-agent', ''), 0, 500) ?: null,
        ]);

        // Session token'ı cookie'ye yaz (3 ay) — kullanıcı yarıda bıraktıysa devam edebilir
        Cookie::queue('uni_match_session', $token, 60 * 24 * 90);

        return redirect()->route('uni-match.step', ['n' => 1, 't' => $token]);
    }

    public function step(Request $request, int $n): View|RedirectResponse
    {
        $response = $this->resolveSession($request);
        if (! $response) return redirect()->route('uni-match.start');

        $total = $this->schema->totalSteps();
        $n = max(1, min($total, $n));

        // Bu step'in tanımı
        $stepDef = $this->schema->stepAt($n);
        if (! $stepDef) return redirect()->route('uni-match.start');

        // preferred_cities adımı için tüm şehir kataloğunu hazırla (autocomplete)
        $allCities = null;
        if (($stepDef['key'] ?? '') === 'preferred_cities') {
            $allCities = \Illuminate\Support\Facades\Cache::remember('unimatch.all_cities', 3600, function () {
                return \DB::table('programs')
                    ->select('location')
                    ->whereNotNull('location')->where('location', '!=', '')
                    ->groupBy('location')
                    ->orderByRaw('COUNT(*) DESC')
                    ->pluck('location')
                    ->values()
                    ->all();
            });
        }

        return view('uni-match.step', [
            'response'   => $response,
            'stepDef'    => $stepDef,
            'currentStep'=> $n,
            'totalSteps' => $total,
            'progress'   => (int) round(($n / $total) * 100),
            'answer'     => $response->getAnswer($stepDef['key']),
            'allCities'  => $allCities,
        ]);
    }

    public function saveStep(Request $request, int $n): RedirectResponse
    {
        $response = $this->resolveSession($request);
        if (! $response) return redirect()->route('uni-match.start');

        $total = $this->schema->totalSteps();
        $stepDef = $this->schema->stepAt($n);
        if (! $stepDef) return redirect()->route('uni-match.start');

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

        // Step timestamp — drop-off analizi için
        $stamps = is_array($response->step_timestamps) ? $response->step_timestamps : [];
        $stamps['step_' . $n] = now()->toIso8601String();
        $response->step_timestamps = $stamps;

        $response->save();

        // Critical step events (PostHog) — funnel drop-off detection
        // Step 6: eğitim seviyesi (Bachelor/Master karar noktası)
        // Step 13: aylık bütçe (finansal commitment noktası)
        // Step 17: APS sertifikası (TR-spesifik kritik)
        if (in_array($n, [6, 13, 17], true)) {
            $this->captureFunnelEvent("unimatch_step_{$n}_completed", $response, [
                'step_key'   => $key,
                'step_value' => is_array($value) ? $value : (string) $value,
            ]);
        }

        if ($n >= $total) {
            return redirect()->route('uni-match.complete');
        }

        // Mid-funnel lead capture: step 12 (yaşam masrafı seçimi) sonrası soft gate
        // Sadece henüz email/phone yoksa ve atlamadıysa
        if ($n === 12 && empty($response->lead_email) && empty($response->lead_phone) && empty($response->getAnswer('_lead_capture_skipped'))) {
            return redirect()->route('uni-match.lead-capture.form');
        }

        return redirect()->route('uni-match.step', ['n' => $n + 1]);
    }

    /** Mid-funnel lead capture formu (step 12 sonrası). */
    public function leadCaptureForm(Request $request): View|RedirectResponse
    {
        $response = $this->resolveSession($request);
        if (! $response) return redirect()->route('uni-match.start');
        if (! empty($response->lead_email) || ! empty($response->lead_phone)) {
            return redirect()->route('uni-match.step', ['n' => 13]);
        }

        $this->captureFunnelEvent('unimatch_lead_gate_shown', $response);

        return view('uni-match.lead-capture', [
            'response' => $response,
            'progress' => (int) round(($response->current_step / 19) * 100),
        ]);
    }

    /** Lead bilgisini kaydet, step 13'e geç. */
    public function leadCaptureSubmit(Request $request): RedirectResponse
    {
        $response = $this->resolveSession($request);
        if (! $response) return redirect()->route('uni-match.start');

        $data = $request->validate([
            'first_name' => ['nullable', 'string', 'max:80'],
            'email'      => ['nullable', 'email', 'max:200'],
            'phone'      => ['nullable', 'string', 'max:30'],
            'consent'    => ['nullable', 'boolean'],
        ]);

        // Email VEYA phone zorunlu (ikisi de boşsa skip'e yönlendir)
        if (empty($data['email']) && empty($data['phone'])) {
            return redirect()->route('uni-match.lead-capture.skip');
        }

        $response->fill([
            'lead_first_name'        => $data['first_name'] ?? null,
            'lead_email'             => $data['email'] ?? null,
            'lead_phone'             => $data['phone'] ?? null,
            'lead_consent_marketing' => (bool) ($data['consent'] ?? false),
            'lead_captured_at'       => now(),
        ])->save();

        $this->captureFunnelEvent('unimatch_lead_captured', $response, [
            'method'   => $data['email'] ? 'email' : 'phone',
            'consent'  => (bool) ($data['consent'] ?? false),
        ]);

        // KVKK opt-in varsa hemen karşılama mesajları (WhatsApp + email kalan adımlarda gönderilir)
        if (! empty($data['phone']) && ! empty($data['consent'])) {
            $this->sendWhatsappGreeting($response);
        }

        // PDF indirmek isterken yönlendirildi → şimdi PDF'i ver
        if ($response->getAnswer('_pdf_requested') && $response->isCompleted()) {
            $response->setAnswer('_pdf_requested', false); // Reset
            $response->save();
            return redirect()->route('uni-match.result.pdf');
        }

        return redirect()->route('uni-match.step', ['n' => 13])
            ->with('success', 'Bilgilerin kaydedildi — sonuçları sana ileteceğiz.');
    }

    /**
     * Manager/dealer'a yeni lead WhatsApp bildirimi.
     * ENV: UNIMATCH_LEAD_NOTIFY_PHONE setli olmalı.
     */
    private function notifyTeamOfNewLead(UniMatchResponse $response, $guest, array $answers): void
    {
        $notifyPhone = config('services.whatsapp.unimatch_lead_notify_phone');
        if (empty($notifyPhone)) return;

        try {
            $name = $response->lead_first_name ?: '(isim yok)';
            $contact = $response->lead_email ?: ($response->lead_phone ?: 'iletişim yok');
            $degree = $answers['target_degree'] ?? '?';
            $field = $answers['target_field'] ?? '?';
            $lang = $answers['study_language'] ?? '?';
            $cities = is_array($answers['preferred_cities'] ?? null)
                ? implode(', ', array_slice($answers['preferred_cities'], 0, 3))
                : '?';
            $brand = config('brand.name', 'MentorDE');
            $managerUrl = url('/manager/students/' . ($guest->id ?? '?'));

            $body = "🎯 YENİ LEAD — {$brand} UniMatch\n\n"
                . "👤 {$name}\n"
                . "📞 {$contact}\n\n"
                . "📚 {$degree} · {$field} · {$lang}\n"
                . "📍 {$cities}\n\n"
                . ($response->source ? "🎯 Kaynak: {$response->source}\n" : '')
                . "👉 {$managerUrl}";

            app(\App\Services\WhatsAppService::class)->sendText($notifyPhone, $body);

            $this->captureFunnelEvent('unimatch_team_notified', $response, [
                'guest_id' => $guest->id,
            ]);
        } catch (\Throwable $e) {
            \Log::warning('UniMatch.team_notify_failed', [
                'session' => $response->session_token,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    /**
     * Lead'e anlık WhatsApp karşılama mesajı.
     * Async hale getirmek için queue'ya atılabilir; şu an sync (~1 sn API çağrısı).
     */
    private function sendWhatsappGreeting(UniMatchResponse $response): void
    {
        try {
            $name = $response->lead_first_name ?: 'Merhaba';
            $brand = config('brand.name', 'MentorDE');
            $url = url('/uni-match/step/' . $response->current_step . '?t=' . $response->session_token);

            $body = "Merhaba {$name} 👋\n\n"
                . "{$brand} UniMatch'ı kullandığın için teşekkürler. Bilgilerini kaydettik — sonuçlarını sana en kısa sürede ileteceğiz.\n\n"
                . "📌 Yarıda kaldıysan kaldığın yerden devam et:\n{$url}\n\n"
                . "Soruların için bu numaradan yazabilirsin. İyi başvurular!";

            $sent = app(\App\Services\WhatsAppService::class)->sendText($response->lead_phone, $body);

            $this->captureFunnelEvent('unimatch_whatsapp_greeting_sent', $response, [
                'success' => $sent,
            ]);
        } catch (\Throwable $e) {
            \Log::warning('UniMatch.whatsapp_greeting_failed', [
                'session' => $response->session_token,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    /** Skip — lead'i atla, devam et. */
    public function leadCaptureSkip(Request $request): RedirectResponse
    {
        $response = $this->resolveSession($request);
        if (! $response) return redirect()->route('uni-match.start');

        // Skip'i answer'a işaretle (tekrar göstermemek için)
        $response->setAnswer('_lead_capture_skipped', true);
        $response->save();

        $this->captureFunnelEvent('unimatch_lead_gate_skipped', $response);

        return redirect()->route('uni-match.step', ['n' => 13]);
    }

    /** PostHog'a wizard funnel event'i gönder (anonim funnel — distinctId = session_token). */
    private function captureFunnelEvent(string $event, UniMatchResponse $response, array $extra = []): void
    {
        try {
            app(\App\Services\Analytics\AnalyticsService::class)->capture(
                $event,
                array_merge([
                    'session_token' => $response->session_token,
                    'company_id'    => $response->company_id,
                    'current_step'  => $response->current_step,
                    'source'        => $response->source,
                    'referrer'      => $response->referrer,
                ], $extra),
                $response->session_token
            );
        } catch (\Throwable $e) {
            \Log::warning('UniMatch.event_failed', ['event' => $event, 'error' => $e->getMessage()]);
        }
    }

    public function complete(Request $request): RedirectResponse
    {
        $response = $this->resolveSession($request);
        if (! $response) return redirect()->route('uni-match.start');

        // Recommendation engine çalıştır
        $recs = $this->engine->recommend($response, 10);

        $response->recommendations = $recs;
        $response->completed_at = now();
        $response->last_active_at = now();
        $response->save();

        return redirect()->route('uni-match.result');
    }

    public function result(Request $request): View|RedirectResponse
    {
        $response = $this->resolveSession($request);
        if (! $response || ! $response->isCompleted()) {
            return redirect()->route('uni-match.start');
        }

        // PostHog event — sadece ilk görüntülemede
        if ($response->result_viewed_at === null) {
            $response->result_viewed_at = now();
            $response->save();

            $this->captureFunnelEvent('unimatch_result_reached', $response, [
                'recommendations_count' => count($response->recommendations ?? []),
                'top_score'             => ($response->recommendations[0]['match_score'] ?? null),
            ]);
        }

        // Sosyal proof — son 7 gün wizard tamamlama sayısı (gerçek veri)
        $socialProof = \Illuminate\Support\Facades\Cache::remember(
            'unimatch.social_proof',
            300, // 5 dk cache
            fn() => UniMatchResponse::query()
                ->whereNotNull('completed_at')
                ->where('completed_at', '>=', now()->subDays(7))
                ->count()
        );

        return view('uni-match.result', [
            'response'        => $response,
            'recommendations' => $response->recommendations ?? [],
            'socialProof'     => $socialProof,
        ]);
    }

    /**
     * Favori program toggle (AJAX) — max 3 program.
     */
    public function toggleFavorite(Request $request): \Illuminate\Http\JsonResponse
    {
        $response = $this->resolveSession($request);
        if (! $response) {
            return response()->json(['ok' => false, 'error' => 'session_not_found'], 404);
        }

        $programId = (string) $request->input('program_id', '');
        if ($programId === '') {
            return response()->json(['ok' => false, 'error' => 'program_id_required'], 422);
        }

        $favorites = is_array($response->favorite_program_ids) ? $response->favorite_program_ids : [];

        if (in_array($programId, $favorites, true)) {
            $favorites = array_values(array_diff($favorites, [$programId]));
            $action = 'removed';
        } else {
            if (count($favorites) >= 3) {
                return response()->json([
                    'ok' => false,
                    'error' => 'max_3_favorites',
                    'message' => 'En fazla 3 program favorileyebilirsin. Önce birini kaldır.',
                    'favorites' => $favorites,
                ], 422);
            }
            $favorites[] = $programId;
            $action = 'added';
        }

        $response->favorite_program_ids = $favorites;
        $response->save();

        return response()->json([
            'ok' => true,
            'action' => $action,
            'favorites' => $favorites,
            'count' => count($favorites),
        ]);
    }

    /**
     * Sonuç sayfasını PDF olarak indir — lead magnet.
     * Email yoksa lead capture'a yönlendir, oradan dönünce PDF.
     */
    public function resultPdf(Request $request)
    {
        $response = $this->resolveSession($request);
        if (! $response || ! $response->isCompleted()) {
            return redirect()->route('uni-match.start');
        }

        // Lead magnet gate: email yoksa lead capture sayfasına yönlendir
        // (PDF indirme için iletişim bilgisi şart — yumuşak gate)
        if (empty($response->lead_email) && empty($response->lead_phone)) {
            $response->setAnswer('_pdf_requested', true);
            $response->save();

            $this->captureFunnelEvent('unimatch_pdf_gated', $response);

            return redirect()->route('uni-match.lead-capture.form')
                ->with('info', '📄 PDF indirebilmek için iletişim bilginizi bırakın — sonuçlarınız kaydedilecek.');
        }

        // Favori-only mode: ?favorites=1 ile sadece yıldızlanan programları göster
        $favoritesOnly = (bool) $request->query('favorites');
        $recommendations = $response->recommendations ?? [];
        if ($favoritesOnly) {
            $favIds = (array) ($response->favorite_program_ids ?? []);
            if (! empty($favIds)) {
                $recommendations = array_values(array_filter(
                    $recommendations,
                    fn ($r) => in_array($r['program_id'] ?? null, $favIds, true)
                ));
            }
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('uni-match.result-pdf', [
            'response'        => $response,
            'recommendations' => $recommendations,
            'firstName'       => $response->lead_first_name ?: 'Aday Öğrenci',
            'generatedAt'     => now(),
            'brand'           => config('brand.name', 'MentorDE'),
            'favoritesOnly'   => $favoritesOnly,
        ])->setPaper('a4', 'portrait');

        $this->captureFunnelEvent('unimatch_pdf_downloaded', $response, [
            'recommendations_count' => count($recommendations),
            'favorites_only'        => $favoritesOnly,
        ]);

        $suffix = $favoritesOnly ? '-Favorilerim' : '';
        $filename = 'UniMatch-Sonuclarim' . $suffix . '-' . now()->format('Y-m-d') . '.pdf';
        return $pdf->download($filename);
    }

    /**
     * Wizard cevaplarını guest_application'a aktar.
     * Form pre-fill ile MentorDE kayıt akışına yönlendirir.
     */
    public function convert(Request $request): RedirectResponse
    {
        $response = $this->resolveSession($request);
        if (! $response || ! $response->isCompleted()) {
            return redirect()->route('uni-match.start');
        }

        if ($response->converted_to_guest_id) {
            // Daha önce dönüşmüş — public başvuru formuna yönlendir
            return redirect('/apply')->with('info', 'Daha önce kayıt başlatmıştın, kaldığın yerden devam ediyorsun.');
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

        $this->captureFunnelEvent('unimatch_converted', $response, [
            'guest_id'        => $guest->id,
            'tracking_token'  => $guest->tracking_token,
            'time_to_convert' => $response->started_at && $response->converted_at
                ? $response->started_at->diffInSeconds($response->converted_at) : null,
        ]);

        // Manager/dealer'a WhatsApp bildirimi — yeni lead convert oldu
        $this->notifyTeamOfNewLead($response, $guest, $a);

        return redirect('/apply')->with('success', 'Wizard cevapların form\'a aktarıldı — sadece kalan bilgileri tamamla.');
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
    private function resolveSession(Request $request): ?UniMatchResponse
    {
        $token = (string) ($request->query('t') ?: $request->cookie('uni_match_session', ''));
        if ($token === '') return null;

        $companyId = (int) ($request->attributes->get('company_id') ?? 1);
        return UniMatchResponse::query()
            ->where('session_token', $token)
            ->where('company_id', $companyId)
            ->first();
    }
}
