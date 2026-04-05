<?php

namespace App\Services;

use App\Http\Controllers\Concerns\UsesRequiredDocuments;
use App\Models\Document;
use App\Models\DmMessage;
use App\Models\GuestApplication;
use App\Models\GuestRegistrationSnapshot;
use App\Models\Marketing\CmsContent;
use App\Models\ProcessOutcome;
use App\Support\ApplicationCountryCatalog;
use App\Support\SchemaCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * PortalController için ortak view verisi üretir.
 * buildBaseViewData() + ilgili private helper'lar buraya taşındı.
 * Controller ince kalır; bu servis tek sorumluluk alır.
 */
class GuestViewDataService
{
    use UsesRequiredDocuments;

    public function __construct(
        private readonly GuestRegistrationFieldSchemaService $registrationFieldSchema,
    ) {}

    // ── Ana Metot ─────────────────────────────────────────────────────────────

    /**
     * @return array<string,mixed>
     */
    public function build(Request $request, ?GuestApplication $guest): array
    {
        $studentId = (string) ($guest?->converted_student_id ?? '');
        $outcomes = $studentId === ''
            ? collect()
            : ProcessOutcome::query()
                ->where('student_id', $studentId)
                ->latest()
                ->limit(10)
                ->get(['process_step', 'outcome_type', 'details_tr', 'created_at']);

        $formCompleted    = (bool) ($guest?->registration_form_submitted_at ?? false);
        $formDraftComplete = false;
        $formRequiredTotal = 0;
        $formRequiredFilled = 0;
        if ($guest) {
            $companyId = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
            $groups = $this->registrationFieldSchema->groups($companyId);
            $draft  = is_array($guest->registration_form_draft) ? $guest->registration_form_draft : [];
            $allFields = collect($groups)->flatMap(fn ($g) => $g['fields'] ?? []);
            $required  = $allFields->filter(fn ($f) => !empty($f['required']));
            $formRequiredTotal  = (int) $required->count();
            $formRequiredFilled = (int) $required->filter(function ($f) use ($draft, $guest) {
                $k = (string) ($f['key'] ?? '');
                if ($k === '') {
                    return false;
                }
                $v = $draft[$k] ?? ($guest?->{$k} ?? null);
                return trim((string) $v) !== '';
            })->count();
            $formDraftComplete = $formRequiredTotal > 0 && $formRequiredFilled >= $formRequiredTotal;
        }

        $docsCompleted   = (bool) ($guest?->docs_ready ?? false);
        $packageSelected = trim((string) ($guest?->selected_package_code ?? '')) !== '';
        $contractStatus  = (string) ($guest?->contract_status ?? 'not_requested');
        $contractApproved = $contractStatus === 'approved';

        $progress = [
            ['label' => 'Kayıt Formu',      'done' => $formCompleted,                        'url' => '/guest/registration-form'],
            ['label' => 'Belgeler',          'done' => (bool) ($guest?->docs_ready ?? false), 'url' => '/guest/registration-documents'],
            ['label' => 'Paket Seçimi',      'done' => $packageSelected,                      'url' => '/guest/services'],
            ['label' => 'Sözleşme / Onay',  'done' => $contractApproved,                     'url' => '/guest/contract'],
            ['label' => 'Kayıt Tamamlandı', 'done' => $contractApproved && $formCompleted,    'url' => '/guest/dashboard'],
        ];

        // Son adım ("Kayıt Tamamlandı") adım 1 ve 4'ten türetilmiş bir özet göstergesi.
        // Payda olarak kullanılırsa çift sayım yapıp yüzdeyi yanlış atlatır.
        // Yalnızca ilk 4 aksiyonable adım üzerinden hesapla.
        $actionableSteps    = collect($progress)->slice(0, -1);
        $progressPercent    = (int) round(
            $actionableSteps->where('done', true)->count() / max(1, $actionableSteps->count()) * 100
        );

        $profileCompletionPercent = $this->calculateProfileCompletionPercent($guest);

        $docsChecklistStats = ['required_total' => 0, 'required_uploaded' => 0, 'percent' => 0];
        $missingRequiredDocuments = [];
        $registrationSnapshots    = collect();

        if ($guest) {
            $ownerId = $this->resolveDocumentOwnerId($guest);
            $uploadedCodes = Document::query()
                ->where('student_id', $ownerId)
                ->with('category:id,code')
                ->get()
                ->map(fn (Document $d) => (string) ($d->category->code ?? ''))
                ->filter()
                ->values()
                ->all();

            $checklist = collect($this->requiredDocumentsByApplicationType(
                (string) ($guest->application_type ?? ''),
                $uploadedCodes
            ));
            $requiredTotal    = (int) $checklist->where('is_required', true)->count();
            $requiredUploaded = (int) $checklist->where('is_required', true)->where('uploaded', true)->count();
            $docsChecklistStats = [
                'required_total'    => $requiredTotal,
                'required_uploaded' => $requiredUploaded,
                'percent'           => $requiredTotal > 0 ? (int) round(($requiredUploaded / $requiredTotal) * 100) : 0,
            ];
            $docsCompleted = $requiredTotal > 0
                ? $requiredUploaded >= $requiredTotal
                : (bool) ($guest->docs_ready ?? false);

            $missingRequiredDocuments = $checklist
                ->where('is_required', true)
                ->where('uploaded', false)
                ->values()
                ->map(fn ($row) => [
                    'document_code' => (string) ($row['document_code'] ?? ''),
                    'name'          => (string) ($row['name'] ?? ''),
                ])
                ->all();

            if (SchemaCache::hasTable('guest_registration_snapshots')) {
                $registrationSnapshots = GuestRegistrationSnapshot::query()
                    ->where('guest_application_id', (int) $guest->id)
                    ->orderByDesc('snapshot_version')
                    ->limit(10)
                    ->get(['id', 'snapshot_version', 'submitted_by_email', 'meta_json', 'submitted_at']);
            }
        }

        // docs_ready DB sync — hesaplanan değer ile DB tutarsızlığını kapat
        if ($guest && (bool) $guest->docs_ready !== $docsCompleted) {
            $guest->withoutTimestamps(fn () => $guest->update(['docs_ready' => $docsCompleted]));
        }

        return [
            'guest'                    => $guest,
            'user'                     => $request->user(),
            'applicationCountries'     => ApplicationCountryCatalog::options(),
            'outcomes'                 => $outcomes,
            'formCompleted'            => $formCompleted,
            'docsCompleted'            => $docsCompleted,
            'packageSelected'          => $packageSelected,
            'contractApproved'         => $contractApproved,
            'progress'                 => $progress,
            'progressPercent'          => $progressPercent,
            'profileCompletionPercent' => $profileCompletionPercent,
            'conversionReady'          => $formCompleted && $docsCompleted && $packageSelected && $contractApproved,
            'formDraftComplete'        => $formDraftComplete,
            'formRequiredTotal'        => $formRequiredTotal,
            'formRequiredFilled'       => $formRequiredFilled,
            'docsChecklistStats'       => $docsChecklistStats,
            'registrationSnapshots'    => $registrationSnapshots,
            'contractStatus'           => $contractStatus,
            'guestDmUnread'            => $guest ? $this->resolveGuestDmUnreadCount($guest) : 0,
            'missingRequiredDocuments' => $missingRequiredDocuments,
            'socialProof'              => $this->resolveSocialProof(),
        ];
    }

    // ── Public Yardımcılar ───────────────────────────────────────────────────

    public function calculateProfileCompletionPercent(?GuestApplication $guest): int
    {
        if (!$guest) {
            return 0;
        }

        $draft = is_array($guest->registration_form_draft) ? $guest->registration_form_draft : [];
        $has = fn (?string $value): bool => trim((string) $value) !== '';
        $hasDraft = fn (string $key): bool => trim((string) ($draft[$key] ?? '')) !== '';

        $checks = [
            $has($guest->first_name),
            $has($guest->last_name),
            $has($guest->email),
            $has($guest->phone),
            $has($guest->gender),
            $has($guest->application_country),
            $has($guest->communication_language),
            $has($guest->application_type),
            $has($guest->target_term),
            $has($guest->target_city),
            $has($guest->language_level),
            $hasDraft('address_country'),
            $hasDraft('education_level'),
            $hasDraft('passport_number'),
            $hasDraft('motivation_text'),
            (bool) $guest->kvkk_consent,
        ];

        $done = collect($checks)->filter(fn ($v) => $v === true)->count();
        return (int) round(($done / max(1, count($checks))) * 100);
    }

    /**
     * @return array<string,\Illuminate\Support\Collection<int,\App\Models\Marketing\CmsContent>>
     */
    public function loadDashboardContentBlocks(): array
    {
        $published = CmsContent::query()
            ->where('status', 'published')
            ->orderByDesc('published_at')
            ->orderByDesc('id');

        $cols = ['id', 'title_tr', 'summary_tr', 'slug', 'category', 'type'];

        return [
            'success_stories'  => (clone $published)->where(fn ($q) => $q->where('category', 'success_story')->orWhere('type', 'success_story'))->limit(3)->get($cols),
            'city_guides'      => (clone $published)->where(fn ($q) => $q->where('category', 'city_guide')->orWhere('type', 'city_guide'))->limit(3)->get($cols),
            'university_guides'=> (clone $published)->where(fn ($q) => $q->where('category', 'university_guide')->orWhere('type', 'university_guide'))->limit(3)->get($cols),
            'events'           => (clone $published)->where(fn ($q) => $q->where('category', 'event')->orWhere('type', 'event'))->limit(4)->get(array_merge($cols, ['published_at'])),
            'campaign_contents'=> (clone $published)->where(fn ($q) => $q->where('category', 'campaign')->orWhere('type', 'campaign'))->limit(4)->get($cols),
        ];
    }

    // ── Private Yardımcılar ──────────────────────────────────────────────────

    private function resolveGuestDmUnreadCount(GuestApplication $guest): int
    {
        $companyId = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;

        return (int) \App\Models\DmMessage::query()
            ->whereIn('thread_id', function ($sub) use ($guest, $companyId): void {
                $sub->select('id')
                    ->from('dm_threads')
                    ->where('thread_type', 'guest')
                    ->where('guest_application_id', (int) $guest->id)
                    ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId));
            })
            ->where('sender_role', '!=', 'guest')
            ->where('is_read_by_participant', false)
            ->count();
    }

    public function resolveDocumentOwnerId(GuestApplication $guest): string
    {
        $studentId = trim((string) ($guest->converted_student_id ?? ''));
        if ($studentId !== '') {
            return $studentId;
        }
        return 'GST-' . str_pad((string) $guest->id, 8, '0', STR_PAD_LEFT);
    }

    private function resolveSocialProof(): array
    {
        return Cache::remember('guest_social_proof', 3600, function () {
            $totalStudents = GuestApplication::where('converted_to_student', true)->count();
            $totalUnis = (int) DB::table('student_university_applications')
                ->whereIn('status', ['accepted', 'conditional_accepted'])
                ->distinct('university_code')
                ->count('university_code');
            return [
                'total_students'   => $totalStudents,
                'total_unis'       => max(50, $totalUnis),
                'satisfaction_pct' => 95,
            ];
        });
    }
}
