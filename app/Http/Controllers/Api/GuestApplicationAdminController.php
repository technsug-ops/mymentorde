<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GuestApplication;
use App\Models\MessageTemplate;
use App\Models\NotificationDispatch;
use App\Models\ProcessOutcome;
use App\Models\StudentAssignment;
use App\Models\StudentType;
use App\Models\User;
use App\Services\InternalNoteService;
use App\Services\LeadSourceTrackingService;
use App\Services\NotificationService;
use App\Services\TaskAutomationService;
use App\Services\EventLogService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class GuestApplicationAdminController extends Controller
{
    public function __construct(
        private readonly InternalNoteService $internalNoteService,
        private readonly LeadSourceTrackingService $leadSourceTrackingService,
        private readonly TaskAutomationService $taskAutomationService,
        private readonly EventLogService $eventLogService,
        private readonly NotificationService $notificationService,
    )
    {
    }

    public function index(Request $request)
    {
        $companyId = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
        $converted = $request->query('converted');
        $status = trim((string) $request->query('status', ''));
        $archived = $request->query('archived');

        $rows = GuestApplication::query()
            ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
            ->when($converted !== null, fn ($q) => $q->where('converted_to_student', filter_var($converted, FILTER_VALIDATE_BOOLEAN)))
            ->when($status !== '', fn ($q) => $q->where('lead_status', $status))
            ->when($archived !== null, fn ($q) => $q->where('is_archived', filter_var($archived, FILTER_VALIDATE_BOOLEAN)))
            ->latest()
            ->limit(200)
            ->get();

        return $rows->map(function (GuestApplication $row): array {
            $readiness = $this->conversionReadinessDetails($row);
            return array_merge($row->toArray(), [
                'conversion_ready' => (bool) $readiness['ready'],
                'conversion_missing' => $readiness['missing'],
                'conversion_checks' => $readiness['checks'],
            ]);
        })->values();
    }

    public function convert(GuestApplication $guestApplication, Request $request)
    {
        $this->assertCompanyAccess($guestApplication);
        $companyId = (int) ($guestApplication->company_id ?: (app()->bound('current_company_id') ? (int) app('current_company_id') : 0));
        if ($guestApplication->converted_to_student) {
            return ApiResponse::error(
                ApiResponse::ERR_GUEST_CONFLICT,
                'Bu kayit zaten studenta donusturulmus.',
                ['guest_id' => $guestApplication->id],
                409
            );
        }
        $readiness = $this->conversionReadinessDetails($guestApplication);
        $requestedSeniorEmail = trim((string) $request->input('senior_email', ''));
        if (!$readiness['ready']) {
            return ApiResponse::error(
                ApiResponse::ERR_GUEST_LOCKED,
                'Donusum kosullari tamamlanmadi.',
                ['ready' => false, 'missing' => $readiness['missing'], 'checks' => $readiness['checks']]
            );
        }

        $data = $request->validate([
            'senior_email' => [
                'nullable',
                'email',
                Rule::exists('users', 'email')->where(function ($q) use ($companyId) {
                    $q->whereIn('role', ['senior', 'mentor'])
                        ->where('is_active', true);
                    if ($companyId > 0) {
                        $q->where('company_id', $companyId);
                    }
                }),
            ],
            'branch' => ['nullable', 'string', 'max:64'],
            'dealer_id' => ['nullable', 'string', 'max:64'],
        ]);

        $studentTypeCode = $this->mapApplicationTypeToStudentTypeCode((string) $guestApplication->application_type);
        $student = $this->generateStudentIdentityFromType($studentTypeCode);
        $seniorEmail = trim((string) ($data['senior_email'] ?? ''));
        if ($seniorEmail === '') {
            $seniorEmail = $this->pickAutoSeniorEmail($companyId) ?: null;
        }

        $assignment = StudentAssignment::query()->create([
            'company_id' => $companyId > 0 ? $companyId : null,
            'student_id' => $student['student_id'],
            'internal_sequence' => $student['internal_sequence'],
            'senior_email' => $seniorEmail,
            'branch' => trim((string) ($data['branch'] ?? $guestApplication->branch ?? '')) ?: null,
            'risk_level' => 'normal',
            'payment_status' => 'ok',
            'dealer_id' => trim((string) ($data['dealer_id'] ?? $guestApplication->dealer_code ?? '')) ?: null,
            'student_type' => $studentTypeCode,
            'is_archived' => false,
        ]);

        // Dealer komisyon kaydını otomatik başlat
        $dealerIdStr = trim((string) ($assignment->dealer_id ?? ''));
        if ($dealerIdStr !== '') {
            try {
                $dealerModel = \App\Models\Dealer::query()
                    ->where('code', $dealerIdStr)
                    ->orWhere('id', $dealerIdStr)
                    ->first();
                if ($dealerModel) {
                    app(\App\Services\DealerRevenueService::class)->initializeDealerStudentRevenue(
                        (string) $dealerModel->id,
                        $assignment->student_id,
                        (string) ($dealerModel->dealer_type_code ?: 'standard')
                    );
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $this->createInitialProcessOutcome($assignment->student_id, $guestApplication, $request);
        $this->createInitialInternalNote($assignment->student_id, $guestApplication, $request);
        $welcomeInfo = $this->queueWelcomeNotifications($assignment->student_id, (string) ($assignment->senior_email ?? ''), $guestApplication, $request);

        $guestApplication->update([
            'converted_to_student' => true,
            'converted_student_id' => $assignment->student_id,
            'lead_status' => 'contract_signed',
            'status_message' => 'Student kaydi acildi: '.$assignment->student_id.($welcomeInfo ? " | {$welcomeInfo}" : ''),
        ]);

        // Portal kullanicisi varsa rolunu 'student' yap ve student_id ata
        if ((int) ($guestApplication->guest_user_id ?? 0) > 0) {
            User::where('id', $guestApplication->guest_user_id)
                ->where('role', 'guest')
                ->update([
                    'role' => 'student',
                    'student_id' => $assignment->student_id,
                ]);
        }
        $createdTasks = $this->taskAutomationService->ensureStudentOnboardingTasks(
            studentId: (string) $assignment->student_id,
            seniorEmail: (string) ($assignment->senior_email ?? ''),
            companyId: (int) ($assignment->company_id ?: 1)
        );
        $this->eventLogService->log(
            eventType: 'guest_converted_to_student',
            entityType: 'guest_application',
            entityId: (string) $guestApplication->id,
            message: "Guest #{$guestApplication->id} studenta donusturuldu ({$assignment->student_id}).",
            meta: ['student_id' => (string) $assignment->student_id, 'onboarding_tasks_created' => $createdTasks],
            actorEmail: (string) optional($request->user())->email,
            companyId: (int) ($guestApplication->company_id ?: 0)
        );

        try {
            $this->leadSourceTrackingService->markConverted($guestApplication->fresh());
        } catch (\Throwable $e) {
            report($e);
        }

        return ApiResponse::ok([
            'guest_id' => $guestApplication->id,
            'student_id' => $assignment->student_id,
            'senior_email' => $assignment->senior_email,
        ]);
    }

    public function conversionReadiness(GuestApplication $guestApplication)
    {
        $this->assertCompanyAccess($guestApplication);

        return ApiResponse::ok($this->conversionReadinessDetails($guestApplication));
    }

    public function archiveStale(Request $request)
    {
        $companyId = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
        $data = $request->validate([
            'days' => ['nullable', 'integer', 'min:30', 'max:3650'],
            'include_converted' => ['nullable', 'boolean'],
        ]);

        $days = (int) ($data['days'] ?? 180);
        $includeConverted = (bool) ($data['include_converted'] ?? false);
        $cutoff = now()->subDays($days);

        $query = GuestApplication::query()
            ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
            ->where('is_archived', false)
            ->where('created_at', '<', $cutoff);

        if (!$includeConverted) {
            $query->where('converted_to_student', false);
        }

        $affected = $query->update([
            'is_archived' => true,
            'archived_at' => now(),
            'archived_by' => (string) optional($request->user())->email ?: 'system',
            'archive_reason' => "stale_{$days}_days",
        ]);

        return ApiResponse::ok([
            'affected' => (int) $affected,
            'days' => $days,
            'include_converted' => $includeConverted,
        ]);
    }

    public function approveContract(GuestApplication $guestApplication, Request $request)
    {
        $this->assertCompanyAccess($guestApplication);
        $status = $this->normalizeContractStatus((string) ($guestApplication->contract_status ?? 'not_requested'));
        if ($status !== 'signed_uploaded') {
            return ApiResponse::error(ApiResponse::ERR_UNPROCESSABLE, "Sozlesme onayi icin durum signed_uploaded olmali. Mevcut durum: {$status}");
        }
        if ($msg = $this->contractStateInconsistencyMessage($guestApplication, $status)) {
            return ApiResponse::error(ApiResponse::ERR_UNPROCESSABLE, $msg);
        }
        if (!$guestApplication->contract_signed_at || trim((string) ($guestApplication->contract_signed_file_path ?? '')) === '') {
            return ApiResponse::error(ApiResponse::ERR_UNPROCESSABLE, 'Imzali sozlesme dosyasi/zamani eksik oldugu icin onay verilemez.');
        }

        $guestApplication->update([
            'contract_status' => 'approved',
            'contract_approved_at' => now(),
            'status_message' => 'Sozlesme firma tarafindan onaylandi.',
        ]);
        $this->taskAutomationService->markTasksDoneBySource('guest_contract_requested', (string) $guestApplication->id);
        $this->taskAutomationService->markTasksDoneBySource('guest_contract_signed_uploaded', (string) $guestApplication->id);
        $this->eventLogService->log(
            eventType: 'guest_contract_approved',
            entityType: 'guest_application',
            entityId: (string) $guestApplication->id,
            message: "Guest #{$guestApplication->id} sozlesmesi onaylandi.",
            actorEmail: (string) optional($request->user())->email,
            companyId: (int) ($guestApplication->company_id ?: 0)
        );

        return ApiResponse::ok([
            'guest_id' => $guestApplication->id,
            'contract_status' => $guestApplication->contract_status,
            'contract_approved_at' => (string) $guestApplication->contract_approved_at,
        ]);
    }

    public function rejectContract(GuestApplication $guestApplication, Request $request)
    {
        $this->assertCompanyAccess($guestApplication);
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:300'],
        ]);

        $status = $this->normalizeContractStatus((string) ($guestApplication->contract_status ?? 'not_requested'));
        if ($status !== 'signed_uploaded') {
            return ApiResponse::error(ApiResponse::ERR_UNPROCESSABLE, "Sozlesme reddi icin durum signed_uploaded olmali. Mevcut durum: {$status}");
        }
        if ($msg = $this->contractStateInconsistencyMessage($guestApplication, $status)) {
            return ApiResponse::error(ApiResponse::ERR_UNPROCESSABLE, $msg);
        }
        if (!$guestApplication->contract_signed_at || trim((string) ($guestApplication->contract_signed_file_path ?? '')) === '') {
            return ApiResponse::error(ApiResponse::ERR_UNPROCESSABLE, 'Imzali sozlesme dosyasi/zamani eksik oldugu icin red verilemez.');
        }

        $reason = trim((string) ($data['reason'] ?? ''));
        $statusMessage = 'Sozlesme firma tarafindan reddedildi.';
        if ($reason !== '') {
            $statusMessage .= ' Neden: '.$reason;
        }

        $guestApplication->update([
            'contract_status' => 'rejected',
            'contract_approved_at' => null,
            'status_message' => $statusMessage,
        ]);
        $this->taskAutomationService->markTasksDoneBySource('guest_contract_requested', (string) $guestApplication->id);
        $this->taskAutomationService->markTasksDoneBySource('guest_contract_signed_uploaded', (string) $guestApplication->id);
        $this->eventLogService->log(
            eventType: 'guest_contract_rejected',
            entityType: 'guest_application',
            entityId: (string) $guestApplication->id,
            message: "Guest #{$guestApplication->id} sozlesmesi reddedildi.",
            meta: ['reason' => $reason],
            actorEmail: (string) optional($request->user())->email,
            companyId: (int) ($guestApplication->company_id ?: 0)
        );

        return ApiResponse::ok([
            'guest_id' => $guestApplication->id,
            'contract_status' => $guestApplication->contract_status,
            'status_message' => (string) ($guestApplication->status_message ?? ''),
        ]);
    }

    private function mapApplicationTypeToStudentTypeCode(string $applicationType): string
    {
        $type = strtolower(trim($applicationType));
        return match ($type) {
            'bachelor' => 'bachelor',
            'master' => 'master',
            'ausbildung' => 'ausbildung',
            default => 'bachelor',
        };
    }

    private function generateStudentIdentityFromType(string $studentTypeCode): array
    {
        $input = strtoupper(trim($studentTypeCode));
        $studentType = StudentType::query()
            ->where('code', strtolower($input))
            ->orWhere('code', $input)
            ->orWhere('id_prefix', $input)
            ->first();
        if (!$studentType) {
            abort(422, 'Student type bulunamadi.');
        }

        $prefix = strtoupper((string) $studentType->id_prefix);
        $year = now()->format('y');
        $month = now()->format('m');
        $base = "{$prefix}-{$year}-{$month}";
        $nextSequence = ((int) StudentAssignment::query()->max('internal_sequence')) + 1;

        do {
            $suffix = strtoupper(Str::random(4));
            $suffix = preg_replace('/[^A-Z0-9]/', 'X', $suffix) ?: 'X'.strtoupper(Str::random(3));
            $candidate = "{$base}-{$suffix}";
        } while (StudentAssignment::query()->where('student_id', $candidate)->exists());

        return [
            'student_id' => $candidate,
            'internal_sequence' => $nextSequence,
        ];
    }

    private function pickAutoSeniorEmail(int $companyId = 0): ?string
    {
        $seniors = User::query()
            ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
            ->whereIn('role', ['senior', 'mentor'])
            ->where('is_active', true)
            ->where('auto_assign_enabled', true)
            ->orderBy('id')
            ->get(['email', 'max_capacity']);

        $emails = $seniors->pluck('email')->filter()->values();
        $activeCounts = StudentAssignment::query()
            ->when($companyId > 0, fn ($q) => $q->where('company_id', $companyId))
            ->whereIn('senior_email', $emails)
            ->where('is_archived', false)
            ->selectRaw('senior_email, COUNT(*) as total')
            ->groupBy('senior_email')
            ->pluck('total', 'senior_email');

        $eligible = $seniors->filter(function (User $s) use ($activeCounts) {
            $load = (int) ($activeCounts[$s->email] ?? 0);
            if (!$s->max_capacity) {
                return true;
            }
            return $load < (int) $s->max_capacity;
        })->sortBy(function (User $s) use ($activeCounts) {
            return (int) ($activeCounts[$s->email] ?? 0);
        })->values();

        return $eligible->isEmpty() ? null : (string) $eligible[0]->email;
    }

    private function createInitialProcessOutcome(string $studentId, GuestApplication $guestApplication, Request $request): void
    {
        ProcessOutcome::query()->create([
            'student_id' => $studentId,
            'application_id' => (string) $guestApplication->tracking_token,
            'process_step' => 'application_prep',
            'outcome_type' => 'waitlist',
            'details_tr' => 'Guest kaydindan student kaydina otomatik donusum tamamlandi. Ilk onboarding asamasi olusturuldu.',
            'details_de' => null,
            'details_en' => null,
            'is_visible_to_student' => false,
            'student_notified' => false,
            'added_by' => (string) optional($request->user())->email,
        ]);
    }

    private function createInitialInternalNote(string $studentId, GuestApplication $guestApplication, Request $request): void
    {
        $this->internalNoteService->createSystemNote(
            $studentId,
            "Guest->Student donusumu yapildi | guest_id:{$guestApplication->id} | token:{$guestApplication->tracking_token}",
            (string) optional($request->user())->email,
            'manager'
        );
    }

    private function queueWelcomeNotifications(string $studentId, string $seniorEmail, GuestApplication $guestApplication, Request $request): ?string
    {
        $templates = MessageTemplate::query()
            ->where('category', 'welcome')
            ->where('is_active', true)
            ->orderBy('id')
            ->get();
        if ($templates->isEmpty()) {
            return 'welcome template bulunamadi';
        }

        $recipientName = trim($guestApplication->first_name.' '.$guestApplication->last_name);
        $vars = [
            'student_name' => $recipientName,
            'senior_name' => $seniorEmail,
            'package_name' => 'standart',
            'student_id' => $studentId,
        ];

        $queued = 0;
        foreach ($templates as $template) {
            $this->notificationService->send([
                'template_id'     => $template->id,
                'channel'         => (string) $template->channel,
                'category'        => (string) $template->category,
                'student_id'      => $studentId,
                'recipient_email' => (string) ($guestApplication->email ?? ''),
                'recipient_phone' => (string) ($guestApplication->phone ?? ''),
                'recipient_name'  => $recipientName,
                'body'            => '',
                'variables'       => $vars,
                'source_type'     => 'guest_application',
                'source_id'       => (string) $guestApplication->id,
                'triggered_by'    => (string) optional($request->user())->email,
            ]);
            $queued++;
        }

        $this->internalNoteService->createSystemNote(
            $studentId,
            "Welcome notification queue olusturuldu: {$queued} adet | guest:{$guestApplication->id}",
            (string) optional($request->user())->email,
            'manager'
        );

        return "welcome queue: {$queued}";
    }

    private function renderTemplateText(string $text, array $vars): string
    {
        $out = $text;
        foreach ($vars as $key => $value) {
            $out = str_replace('{{'.$key.'}}', (string) $value, $out);
        }

        return $out;
    }

    private function isConversionReady(GuestApplication $guestApplication): bool
    {
        return $this->conversionReadinessDetails($guestApplication)['ready'];
    }

    private function conversionReadinessDetails(GuestApplication $guestApplication): array
    {
        $formDone = !empty($guestApplication->registration_form_submitted_at);
        $docsDone = (bool) $guestApplication->docs_ready;
        $packageDone = trim((string) ($guestApplication->selected_package_code ?? '')) !== '';
        $contractDone = (string) ($guestApplication->contract_status ?? '') === 'approved';

        $checks = [
            'registration_form_submitted' => $formDone,
            'documents_ready' => $docsDone,
            'package_selected' => $packageDone,
            'contract_approved' => $contractDone,
        ];

        $labels = [
            'registration_form_submitted' => 'on_kayit_formu',
            'documents_ready' => 'belgeler',
            'package_selected' => 'paket_secimi',
            'contract_approved' => 'sozlesme_onayi',
        ];

        $missing = collect($checks)
            ->filter(fn (bool $done) => !$done)
            ->keys()
            ->map(fn (string $key) => $labels[$key] ?? $key)
            ->values()
            ->all();

        return [
            'guest_id' => (int) $guestApplication->id,
            'ready' => count($missing) === 0,
            'checks' => $checks,
            'missing' => $missing,
        ];
    }

    private function assertCompanyAccess(GuestApplication $guestApplication): void
    {
        $companyId = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
        // company_id = 0 ise context set edilmemiş demektir → erişime izin verme
        if ($companyId === 0 || (int) ($guestApplication->company_id ?? 0) !== $companyId) {
            abort(403);
        }
    }

    private function normalizeContractStatus(string $status): string
    {
        $normalized = strtolower(trim($status));

        return in_array($normalized, ['not_requested', 'requested', 'signed_uploaded', 'approved', 'rejected'], true)
            ? $normalized
            : 'not_requested';
    }

    /**
     * POST /api/v1/config/guest-applications/bulk-assign
     * Birden fazla guest application'a senior atar.
     */
    public function bulkAssign(Request $request)
    {
        $data = $request->validate([
            'guest_ids'    => ['required', 'array', 'min:1', 'max:100'],
            'guest_ids.*'  => ['integer', 'min:1'],
            'senior_email' => [
                'required',
                'email',
                \Illuminate\Validation\Rule::exists('users', 'email')
                    ->where(fn ($q) => $q->whereIn('role', ['senior', 'mentor'])->where('is_active', true)),
            ],
        ]);

        $seniorEmail = (string) $data['senior_email'];
        $guestIds    = array_unique(array_map('intval', (array) $data['guest_ids']));
        $cid         = (int) optional($request->user())->company_id;

        $updated = GuestApplication::query()
            ->whereIn('id', $guestIds)
            ->when($cid > 0, fn ($q) => $q->where('company_id', $cid))
            ->whereNull('converted_student_id')
            ->update(['assigned_senior_email' => $seniorEmail]);

        return response()->json([
            'ok'      => true,
            'updated' => $updated,
            'senior_email' => $seniorEmail,
        ]);
    }

    private function contractStateInconsistencyMessage(GuestApplication $guest, string $status): ?string
    {
        $hasSnapshot = trim((string) ($guest->contract_snapshot_text ?? '')) !== '';
        $hasTemplate = trim((string) ($guest->contract_template_code ?? '')) !== '';
        $hasRequestedAt = !empty($guest->contract_requested_at);
        $hasSignedFile = trim((string) ($guest->contract_signed_file_path ?? '')) !== '';
        $hasSignedAt = !empty($guest->contract_signed_at);

        if (in_array($status, ['requested', 'signed_uploaded', 'approved', 'rejected'], true)
            && (!$hasSnapshot || !$hasTemplate || !$hasRequestedAt)) {
            return 'Sozlesme kaydi tutarsiz: snapshot/template/requested_at eksik. Operations/Manager kontrol etmelidir.';
        }

        if (in_array($status, ['signed_uploaded', 'approved'], true) && (!$hasSignedFile || !$hasSignedAt)) {
            return 'Sozlesme kaydi tutarsiz: imzali dosya veya imza zamani kaydi eksik.';
        }

        if ($status === 'approved' && empty($guest->contract_approved_at)) {
            return 'Sozlesme kaydi tutarsiz: approved durumunda contract_approved_at kaydi eksik.';
        }

        return null;
    }
}
