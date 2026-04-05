<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\UsesServicePackages;
use App\Models\GuestApplication;
use App\Models\GuestTicket;
use App\Models\GuestTicketReply;
use App\Models\MarketingTask;
use App\Models\StudentAssignment;
use App\Models\User;
use App\Services\ContractTemplateService;
use App\Services\EventLogService;
use App\Services\StudentGuestResolver;
use App\Services\TaskAutomationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StudentContractController extends Controller
{
    use UsesServicePackages;

    public function __construct(
        private readonly ContractTemplateService $contractTemplateService,
        private readonly EventLogService $eventLogService,
        private readonly TaskAutomationService $taskAutomationService
    ) {
    }

    private function resolveStudentGuest(Request $request): ?GuestApplication
    {
        return app(StudentGuestResolver::class)->resolveForUser($request->user());
    }

    private function normalizeContractStatus(string $status): string
    {
        $normalized = strtolower(trim($status));
        return $normalized !== '' ? $normalized : 'not_requested';
    }

    private function canStudentRequestContractAddendum(GuestApplication $guest): bool
    {
        $status = $this->normalizeContractStatus((string) ($guest->contract_status ?? 'not_requested'));
        return in_array($status, ['requested', 'signed_uploaded', 'rejected'], true);
    }

    private function contractStateHasInconsistency(GuestApplication $guest, ?string $normalizedStatus = null): bool
    {
        $status = $normalizedStatus ?: $this->normalizeContractStatus((string) ($guest->contract_status ?? 'not_requested'));
        $hasSnapshot = trim((string) ($guest->contract_snapshot_text ?? '')) !== '';
        $hasTemplate = trim((string) ($guest->contract_template_code ?? '')) !== '' || (int) ($guest->contract_template_id ?? 0) > 0;
        $hasSignedFile = trim((string) ($guest->contract_signed_file_path ?? '')) !== '';
        $hasRequestedAt = !empty($guest->contract_requested_at);
        $hasSignedAt = !empty($guest->contract_signed_at);
        $hasApprovedAt = !empty($guest->contract_approved_at);

        if (in_array($status, ['requested', 'signed_uploaded', 'approved', 'rejected'], true) && (!$hasSnapshot || !$hasTemplate)) {
            return true;
        }
        if (in_array($status, ['requested', 'signed_uploaded', 'approved', 'rejected'], true) && !$hasRequestedAt) {
            return true;
        }
        if (in_array($status, ['signed_uploaded', 'approved'], true) && !$hasSignedFile) {
            return true;
        }
        if (in_array($status, ['signed_uploaded', 'approved'], true) && !$hasSignedAt) {
            return true;
        }
        if ($status === 'approved' && !$hasApprovedAt) {
            return true;
        }
        if ($status === 'not_requested' && ($hasSnapshot || $hasTemplate || $hasSignedFile || $hasRequestedAt || $hasSignedAt || $hasApprovedAt)) {
            return true;
        }

        return false;
    }

    private function createStudentServiceTask(GuestApplication $guest, string $title, string $description, string $priority = 'normal'): ?MarketingTask
    {
        $companyId = (int) ($guest->company_id ?: (app()->bound('current_company_id') ? (int) app('current_company_id') : 1));
        if ($companyId <= 0) {
            $companyId = 1;
        }

        $assigneeUserId = 0;
        $studentId = trim((string) ($guest->converted_student_id ?? ''));
        if ($studentId !== '') {
            $assignment = StudentAssignment::query()->where('student_id', $studentId)->first();
            $seniorEmail = strtolower(trim((string) ($assignment->senior_email ?? '')));
            if ($seniorEmail !== '') {
                $assigneeUserId = (int) User::query()
                    ->where('email', strtolower($seniorEmail))
                    ->where('is_active', true)
                    ->value('id');
            }
        }

        if ($assigneeUserId <= 0) {
            $assigneeUserId = (int) User::query()
                ->where('company_id', $companyId)
                ->where('role', User::ROLE_MANAGER)
                ->where('is_active', true)
                ->orderBy('id')
                ->value('id');
        }

        if ($assigneeUserId <= 0) {
            return null;
        }

        return MarketingTask::query()->create([
            'company_id' => $companyId,
            'title' => trim($title) !== '' ? trim($title) : 'Student servis aksiyonu',
            'description' => trim($description),
            'status' => 'todo',
            'priority' => in_array($priority, ['low', 'normal', 'high', 'urgent'], true) ? $priority : 'normal',
            'department' => 'advisory',
            'due_date' => now()->addDay()->toDateString(),
            'assigned_user_id' => $assigneeUserId,
            'created_by_user_id' => null,
            'source_type' => 'student_service_update',
            'source_id' => (string) $guest->id . ':' . now()->format('YmdHis') . ':' . substr((string) Str::uuid(), 0, 8),
            'is_auto_generated' => true,
            'escalate_after_hours' => 24,
        ]);
    }

    public function downloadSignedContract(Request $request)
    {
        $guest = $this->resolveStudentGuest($request);
        abort_if(!$guest, 404, 'Student icin bagli basvuru kaydi bulunamadi.');

        $path = trim((string) ($guest->contract_signed_file_path ?? ''));
        abort_if($path === '', 404, 'Imzali sozlesme dosyasi bulunamadi.');
        abort_unless(Storage::disk('public')->exists($path), 404, 'Imzali sozlesme dosyasi bulunamadi.');

        $ext = pathinfo($path, PATHINFO_EXTENSION);
        $studentId = trim((string) ($guest->converted_student_id ?? ''));
        $downloadName = 'signed_contract';
        if ($studentId !== '') {
            $downloadName .= '_' . $studentId;
        }
        if ($ext !== '') {
            $downloadName .= '.' . $ext;
        }

        return Storage::disk('public')->download($path, $downloadName);
    }

    public function requestContract(Request $request)
    {
        return redirect('/student/contract')->withErrors([
            'contract' => 'Sozlesme ilk talep ve imzalama adimi Guest panelinde tamamlanir. Student panelinde sadece degisiklik/ek talep acabilirsiniz.',
        ]);
    }

    public function uploadSignedContract(Request $request)
    {
        return redirect('/student/contract')->withErrors([
            'contract' => 'Imzali sozlesme yukleme adimi Guest panelinde yapilir. Student panelinde degisiklik talebi acabilirsiniz.',
        ]);
    }

    public function digitalSign(Request $request): \Illuminate\Http\JsonResponse
    {
        $guest = $this->resolveStudentGuest($request);
        if (!$guest) {
            return response()->json(['error' => 'Başvuru kaydınıza erişilemiyor.'], 404);
        }

        $currentStatus = $this->normalizeContractStatus((string) ($guest->contract_status ?? 'not_requested'));
        if (!in_array($currentStatus, ['requested', 'rejected'], true)) {
            return response()->json(['error' => "Dijital imza için mevcut durum uygun değil: {$currentStatus}."], 422);
        }

        $data = $request->validate(['signature_data' => ['required', 'string', 'min:100']]);
        $raw  = trim((string) $data['signature_data']);
        if (str_starts_with($raw, 'data:')) {
            $raw = substr($raw, strpos($raw, ',') + 1);
        }
        if (!base64_decode($raw, strict: true)) {
            return response()->json(['error' => 'Geçersiz imza verisi.'], 422);
        }

        $guest->forceFill([
            'contract_digital_signature_data' => $raw,
            'contract_digital_signed_at'      => now(),
            'contract_digital_sign_ip'        => $request->ip(),
            'contract_status'                 => 'signed_uploaded',
            'contract_signed_at'              => now(),
            'status_message'                  => 'Student dijital imza ile sözleşmeyi imzaladı.',
        ])->save();

        $this->eventLogService->log(
            eventType: 'student_contract_digital_signed',
            entityType: 'guest_application',
            entityId: (string) $guest->id,
            message: "Student #{$guest->converted_student_id} dijital imza ile sözleşmeyi imzaladı.",
            meta: ['ip' => $request->ip()],
            actorEmail: (string) optional($request->user())->email,
            companyId: (int) ($guest->company_id ?: 0)
        );
        $this->taskAutomationService->ensureSignedContractTask($guest);

        return response()->json(['success' => true, 'message' => 'Sözleşme başarıyla imzalandı.']);
    }

    public function withdrawContractRequest(Request $request)
    {
        $guest = $this->resolveStudentGuest($request);
        abort_if(!$guest, 404, 'Başvuru kaydınıza erişilemiyor.');

        $currentStatus = $this->normalizeContractStatus((string) ($guest->contract_status ?? 'not_requested'));
        if ($currentStatus !== 'pending_manager') {
            return redirect('/student/contract')
                ->withErrors(['contract' => "Talebi geri çekmek için durum 'pending_manager' olmalıdır. Mevcut: {$currentStatus}."]);
        }

        $guest->forceFill([
            'contract_status'                => 'not_requested',
            'contract_requested_at'          => null,
            'contract_template_id'           => null,
            'contract_template_code'         => null,
            'contract_snapshot_text'         => null,
            'contract_annex_kvkk_text'       => null,
            'contract_annex_commitment_text' => null,
            'contract_generated_at'          => null,
            'status_message'                 => 'Sözleşme talebi student tarafından geri çekildi.',
        ])->save();

        $this->taskAutomationService->markTasksDoneBySource('guest_contract_requested', (string) $guest->id);
        $this->eventLogService->log(
            eventType: 'student_contract_withdrawn',
            entityType: 'guest_application',
            entityId: (string) $guest->id,
            message: "Student #{$guest->converted_student_id} sözleşme talebini geri çekti.",
            meta: null,
            actorEmail: (string) optional($request->user())->email,
            companyId: (int) ($guest->company_id ?: 0)
        );

        return redirect('/student/contract')->with('status', 'Sözleşme talebiniz geri çekildi.');
    }

    public function requestReopen(Request $request)
    {
        $guest = $this->resolveStudentGuest($request);
        abort_if(!$guest, 404, 'Başvuru kaydınıza erişilemiyor.');

        $currentStatus = $this->normalizeContractStatus((string) ($guest->contract_status ?? 'not_requested'));
        if ($currentStatus !== 'cancelled') {
            return redirect('/student/contract')
                ->withErrors(['contract' => "Yeniden değerlendirme için sözleşme 'cancelled' durumunda olmalıdır. Mevcut: {$currentStatus}."]);
        }

        $data = $request->validate(['reopen_reason' => ['required', 'string', 'max:1000']]);

        $guest->forceFill([
            'contract_status'     => 'reopen_requested',
            'reopen_reason'       => strip_tags(trim((string) $data['reopen_reason'])),
            'reopen_requested_at' => now(),
            'reopen_decided_by'   => null,
            'reopen_decided_at'   => null,
            'status_message'      => 'Student tarafından yeniden değerlendirme talep edildi.',
        ])->save();

        $this->eventLogService->log(
            eventType: 'student_contract_reopen_requested',
            entityType: 'guest_application',
            entityId: (string) $guest->id,
            message: "Student #{$guest->converted_student_id} iptal edilen sözleşme için yeniden değerlendirme talep etti.",
            meta: ['reopen_reason' => trim((string) $data['reopen_reason'])],
            actorEmail: (string) optional($request->user())->email,
            companyId: (int) ($guest->company_id ?: 0)
        );

        return redirect('/student/contract')->with('status', 'Yeniden değerlendirme talebiniz iletildi.');
    }

    public function contractSignedThanks(Request $request)
    {
        return view('student.contract-signed-thanks');
    }

    public function requestContractAddendum(Request $request)
    {
        $guest = $this->resolveStudentGuest($request);
        abort_if(!$guest, 404, 'Student icin bagli basvuru kaydi bulunamadi.');
        $currentStatus = $this->normalizeContractStatus((string) ($guest->contract_status ?? 'not_requested'));
        if (!$this->canStudentRequestContractAddendum($guest)) {
            return redirect('/student/contract')->withErrors([
                'contract' => "Sozlesme guncelleme talebi icin mevcut durum uygun degil: {$currentStatus}.",
            ]);
        }
        if ($this->contractStateHasInconsistency($guest, $currentStatus)) {
            return redirect('/student/contract')->withErrors([
                'contract' => 'Sozlesme durumu sistemde tutarsiz gorunuyor (eksik snapshot/imza dosyasi). Lutfen once manager/operasyon ile kontrol edin.',
            ]);
        }

        $data = $request->validate([
            'subject' => ['required', 'string', 'max:180'],
            'message' => ['required', 'string', 'max:5000'],
            'priority' => ['nullable', 'in:normal,high,urgent'],
            'package_code' => ['nullable', 'string', 'max:64'],
            'extra_service_codes' => ['nullable', 'array'],
            'extra_service_codes.*' => ['string', 'max:64'],
        ]);

        $packages = collect($this->servicePackages())->keyBy('code');
        $extraOptions = collect($this->extraServiceOptions())->keyBy('code');
        $requestedPackageCode = trim((string) ($data['package_code'] ?? ''));
        if ($requestedPackageCode !== '' && !$packages->has($requestedPackageCode)) {
            return redirect('/student/contract')->withErrors(['contract' => 'Gecersiz paket secimi.']);
        }
        $selectedCodes = collect((array) ($data['extra_service_codes'] ?? []))
            ->map(fn ($x) => trim((string) $x))
            ->filter()
            ->unique()
            ->values();
        foreach ($selectedCodes as $code) {
            if (!$extraOptions->has($code)) {
                return redirect('/student/contract')->withErrors(['contract' => "Gecersiz ek hizmet kodu: {$code}"]);
            }
        }

        $packageChanged = false;
        if ($requestedPackageCode !== '') {
            $pkg = (array) $packages->get($requestedPackageCode);
            $guest->selected_package_code = (string) ($pkg['code'] ?? '');
            $guest->selected_package_title = (string) ($pkg['title'] ?? '');
            $guest->selected_package_price = (string) ($pkg['price'] ?? '');
            $guest->package_selected_at = now();
            $packageChanged = true;
        }
        $guest->selected_extra_services = $selectedCodes
            ->map(function (string $code) use ($extraOptions): array {
                $opt = (array) $extraOptions->get($code);
                return [
                    'code' => (string) ($opt['code'] ?? $code),
                    'title' => (string) ($opt['title'] ?? $code),
                    'added_at' => now()->toDateTimeString(),
                ];
            })
            ->all();

        try {
            $snapshot = $this->contractTemplateService->buildSnapshot($guest, (int) ($guest->company_id ?: 0));
        } catch (\Throwable $e) {
            report($e);
            return redirect('/student/contract')->withErrors([
                'contract' => 'Sozlesme metni olusturulurken bir hata olustu. Lutfen tekrar deneyin veya Operations ekibi ile iletisime gecin.',
            ]);
        }
        $guest->forceFill([
            'contract_status' => 'requested',
            'contract_requested_at' => now(),
            'contract_signed_at' => null,
            'contract_signed_file_path' => null,
            'contract_approved_at' => null,
            'contract_template_id' => (int) ($snapshot['template_id'] ?? 0) ?: null,
            'contract_template_code' => (string) ($snapshot['template_code'] ?? ''),
            'contract_snapshot_text' => (string) ($snapshot['body_text'] ?? ''),
            'contract_annex_kvkk_text' => (string) ($snapshot['annex_kvkk_text'] ?? ''),
            'contract_annex_commitment_text' => (string) ($snapshot['annex_commitment_text'] ?? ''),
            'contract_generated_at' => now(),
            'status_message' => 'Student sozlesme guncelleme talebi gonderdi.',
            'notes' => trim(((string) ($guest->notes ?? ''))."\n[Student Contract Update ".now()->format('Y-m-d H:i')."] ".trim((string) $data['message'])),
        ])->save();

        $ticket = GuestTicket::query()->create([
            'guest_application_id' => (int) $guest->id,
            'subject' => trim((string) $data['subject']),
            'message' => trim((string) $data['message']),
            'status' => 'open',
            'priority' => (string) ($data['priority'] ?? 'high'),
            'department' => 'operations',
            'created_by_email' => (string) optional($request->user())->email,
            'last_replied_at' => now(),
            'routed_at' => now(),
        ]);

        GuestTicketReply::query()->create([
            'guest_ticket_id' => (int) $ticket->id,
            'author_role' => 'student',
            'author_email' => (string) optional($request->user())->email,
            'message' => trim((string) $data['message']),
        ]);

        $this->taskAutomationService->ensureContractReviewTask($guest);
        $this->eventLogService->log(
            eventType: 'student_contract_addendum_requested',
            entityType: 'guest_application',
            entityId: (string) $guest->id,
            message: "Student {$guest->converted_student_id} sozlesme guncelleme talebi acti.",
            meta: [
                'ticket_id' => (int) $ticket->id,
                'package_changed' => $packageChanged,
                'package_code' => (string) ($guest->selected_package_code ?? ''),
                'extra_service_codes' => $selectedCodes->all(),
            ],
            actorEmail: (string) optional($request->user())->email,
            companyId: (int) ($guest->company_id ?: 0)
        );

        // Sözleşme imza bildirimi
        $user = $request->user();
        if ($user && $user->email) {
            try {
                \Mail::to($user->email)->queue(new \App\Mail\ContractSignedMail($user->name, 'Öğrenci Sözleşmesi'));
            } catch (\Throwable) {}
        }

        return redirect('/student/contract')->with('status', 'Sozlesme degisiklik talebi olusturuldu.');
    }
}
