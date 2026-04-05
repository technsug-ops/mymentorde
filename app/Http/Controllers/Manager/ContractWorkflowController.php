<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Manager\Concerns\ContractHelperTrait;
use App\Models\ContractAuditLog;
use App\Models\GuestApplication;
use App\Models\User;
use App\Services\ContractTemplateService;
use App\Services\EventLogService;
use App\Services\NotificationService;
use App\Services\TaskAutomationService;
use App\Support\FileUploadRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ContractWorkflowController extends Controller
{
    use ContractHelperTrait;

    public function __construct(
        private readonly ContractTemplateService $contractTemplateService,
        private readonly TaskAutomationService $taskAutomationService,
        private readonly EventLogService $eventLogService,
        private readonly NotificationService $notificationService,
    ) {}

    public function startContract(Request $request): RedirectResponse
    {
        $companyId = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
        $data = $request->validate([
            'guest_id' => ['required', 'integer', 'min:1'],
        ]);

        $guest = GuestApplication::query()
            ->when($companyId > 0, fn ($q) => $q->forCompany($companyId))
            ->where('id', (int) $data['guest_id'])
            ->firstOrFail();

        $currentStatus = $this->normalizeContractStatus((string) ($guest->contract_status ?? 'not_requested'));
        if (! in_array($currentStatus, ['not_requested', 'pending_manager', 'rejected'], true)) {
            return redirect()->route('manager.contract-template.show', [
                'q'        => (string) ($guest->converted_student_id ?? $guest->email),
                'guest_id' => (int) $guest->id,
            ])->withErrors(['contract' => "Sözleşme gönderme için mevcut durum uygun değil: {$currentStatus}."]);
        }

        if (trim((string) ($guest->selected_package_code ?? '')) === '') {
            return redirect()->route('manager.contract-template.show', [
                'q'        => (string) ($guest->converted_student_id ?? $guest->email),
                'guest_id' => (int) $guest->id,
            ])->withErrors(['contract' => 'Manuel sozlesme baslatmadan once ogrenciye paket secimi yapilmalidir.']);
        }

        $snapshot = $this->contractTemplateService->buildSnapshot($guest, (int) ($guest->company_id ?: 0));

        $guest->forceFill([
            'contract_status'                => 'requested',
            'contract_requested_at'          => now(),
            'contract_signed_at'             => null,
            'contract_signed_file_path'      => null,
            'contract_approved_at'           => null,
            'contract_template_id'           => (int) ($snapshot['template_id'] ?? 0) ?: null,
            'contract_template_code'         => (string) ($snapshot['template_code'] ?? ''),
            'contract_snapshot_text'         => (string) ($snapshot['body_text'] ?? ''),
            'contract_annex_kvkk_text'       => (string) ($snapshot['annex_kvkk_text'] ?? ''),
            'contract_annex_commitment_text' => (string) ($snapshot['annex_commitment_text'] ?? ''),
            'contract_annex_payment_text'    => (string) ($snapshot['annex_payment_text'] ?? ''),
            'contract_generated_at'          => now(),
            'status_message'                 => 'Sozlesme manager tarafindan manuel baslatildi.',
        ])->save();

        ContractAuditLog::log(
            guestApplicationId: (int) $guest->id,
            oldStatus: $currentStatus,
            newStatus: 'requested',
            changedBy: (string) optional($request->user())->email,
            note: 'Manuel sözleşme başlatıldı.',
            ip: $request->ip()
        );
        $this->taskAutomationService->ensureContractReviewTask($guest);
        $this->eventLogService->log(
            eventType: 'manager_contract_started',
            entityType: 'guest_application',
            entityId: (string) $guest->id,
            message: "Manager/yetkili kullanici Guest #{$guest->id} icin manuel sozlesme baslatti.",
            meta: ['template_code' => (string) ($snapshot['template_code'] ?? '')],
            actorEmail: (string) optional($request->user())->email,
            companyId: (int) ($guest->company_id ?: 0)
        );
        $this->dispatchContractNotification($guest, 'guest_contract_update', 'manager_contract_started');

        return redirect()->route('manager.contract-template.show', [
            'q'        => (string) ($guest->converted_student_id ?? $guest->email),
            'guest_id' => (int) $guest->id,
        ])->with('status', 'Sozlesme manuel olarak baslatildi.');
    }

    public function decideContract(Request $request): RedirectResponse
    {
        $companyId = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
        $data = $request->validate([
            'guest_id' => ['required', 'integer', 'min:1'],
            'decision' => ['required', 'in:approve,reject'],
            'note'     => ['nullable', 'string', 'max:1000'],
        ]);

        $guest = GuestApplication::query()
            ->when($companyId > 0, fn ($q) => $q->forCompany($companyId))
            ->where('id', (int) $data['guest_id'])
            ->firstOrFail();

        $currentStatus = $this->normalizeContractStatus((string) ($guest->contract_status ?? 'not_requested'));
        if (! in_array($currentStatus, ['requested', 'signed_uploaded', 'rejected', 'approved'], true)) {
            return redirect()->route('manager.contract-template.show', [
                'guest_id' => (int) $guest->id,
                'q'        => (string) ($guest->converted_student_id ?: $guest->email),
            ])->withErrors(['contract' => "Karar islemi icin durum uygun degil: {$currentStatus}."]);
        }
        if ($this->contractStateHasInconsistency($guest, $currentStatus)) {
            return redirect()->route('manager.contract-template.show', [
                'guest_id' => (int) $guest->id,
                'q'        => (string) ($guest->converted_student_id ?: $guest->email),
            ])->withErrors(['contract' => 'Sozlesme kaydinda tutarsizlik var. Once kayitlar duzeltilmeden karar verilemez.']);
        }

        $actor = (string) optional($request->user())->email;
        $note  = trim((string) ($data['note'] ?? ''));

        if ((string) $data['decision'] === 'approve') {
            if ($currentStatus !== 'signed_uploaded') {
                return redirect()->route('manager.contract-template.show', [
                    'guest_id' => (int) $guest->id,
                    'q'        => (string) ($guest->converted_student_id ?: $guest->email),
                ])->withErrors(['contract' => "Onay icin beklenen durum 'signed_uploaded' olmali. Mevcut: {$currentStatus}."]);
            }
            if (trim((string) ($guest->contract_signed_file_path ?? '')) === '' || empty($guest->contract_signed_at)) {
                return redirect()->route('manager.contract-template.show', [
                    'guest_id' => (int) $guest->id,
                    'q'        => (string) ($guest->converted_student_id ?: $guest->email),
                ])->withErrors(['contract' => 'Onay icin ogrencinin imzali sozlesme dosyasi yuklemis olmasi gerekir.']);
            }
            $newStudentId = trim((string) ($guest->converted_student_id ?? ''));
            if ($newStudentId === '') {
                $newStudentId = 'STU-' . str_pad((string) $guest->id, 8, '0', STR_PAD_LEFT);
            }
            $guest->forceFill([
                'contract_status'      => 'approved',
                'contract_approved_at' => now(),
                'converted_to_student' => true,
                'converted_student_id' => $newStudentId,
                'lead_status'          => 'converted',
                'status_message'       => $note !== '' ? "Sozlesme onaylandi: {$note}" : 'Sozlesme onaylandi.',
            ])->save();
            User::query()
                ->where('email', strtolower((string) $guest->email))
                ->where('role', User::ROLE_GUEST)
                ->update(['role' => User::ROLE_STUDENT]);
            ContractAuditLog::log(
                guestApplicationId: (int) $guest->id,
                oldStatus: 'signed_uploaded',
                newStatus: 'approved',
                changedBy: $actor,
                note: $note !== '' ? $note : null,
                ip: $request->ip()
            );
            $this->taskAutomationService->markTasksDoneBySource('guest_contract_signed_uploaded', (string) $guest->id);
            $this->taskAutomationService->markTasksDoneBySource('guest_contract_requested', (string) $guest->id);
            $this->taskAutomationService->markTasksDoneBySource('guest_contract_sales_followup', (string) $guest->id);
            $this->eventLogService->log(
                eventType: 'manager_contract_approved',
                entityType: 'guest_application',
                entityId: (string) $guest->id,
                message: "Guest #{$guest->id} sozlesmesi manager tarafindan onaylandi.",
                meta: ['note' => $note],
                actorEmail: $actor,
                companyId: (int) ($guest->company_id ?: 0)
            );
            $this->dispatchContractNotification($guest, 'guest_contract_update', 'manager_contract_approved');
            $status = 'Sozlesme onaylandi.';
        } else {
            if ($currentStatus !== 'signed_uploaded') {
                return redirect()->route('manager.contract-template.show', [
                    'guest_id' => (int) $guest->id,
                    'q'        => (string) ($guest->converted_student_id ?: $guest->email),
                ])->withErrors(['contract' => "Red karari icin beklenen durum 'signed_uploaded' olmali. Mevcut: {$currentStatus}."]);
            }
            if (trim((string) ($guest->contract_signed_file_path ?? '')) === '' || empty($guest->contract_signed_at)) {
                return redirect()->route('manager.contract-template.show', [
                    'guest_id' => (int) $guest->id,
                    'q'        => (string) ($guest->converted_student_id ?: $guest->email),
                ])->withErrors(['contract' => 'Red karari icin once imzali sozlesme dosyasinin yuklenmis olmasi gerekir.']);
            }
            $guest->forceFill([
                'contract_status'      => 'rejected',
                'contract_approved_at' => null,
                'status_message'       => $note !== '' ? "Sozlesme reddedildi: {$note}" : 'Sozlesme reddedildi.',
            ])->save();
            ContractAuditLog::log(
                guestApplicationId: (int) $guest->id,
                oldStatus: 'signed_uploaded',
                newStatus: 'rejected',
                changedBy: $actor,
                note: $note !== '' ? $note : null,
                ip: $request->ip()
            );
            $this->eventLogService->log(
                eventType: 'manager_contract_rejected',
                entityType: 'guest_application',
                entityId: (string) $guest->id,
                message: "Guest #{$guest->id} sozlesmesi manager tarafindan reddedildi.",
                meta: ['note' => $note],
                actorEmail: $actor,
                companyId: (int) ($guest->company_id ?: 0)
            );
            $this->dispatchContractNotification($guest, 'guest_contract_update', 'manager_contract_rejected');
            $status = 'Sozlesme reddedildi.';
        }

        return redirect()->route('manager.contract-template.show', [
            'guest_id' => (int) $guest->id,
            'q'        => (string) ($guest->converted_student_id ?: $guest->email),
        ])->with('status', $status);
    }

    public function cancelContract(Request $request): RedirectResponse
    {
        $companyId = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;

        $allReasonCodes = collect(config('contract_cancel_reasons', []))
            ->flatMap(fn ($cat) => array_keys((array) ($cat['reasons'] ?? [])))
            ->all();

        $data = $request->validate([
            'guest_id'           => ['required', 'integer', 'min:1'],
            'cancel_category'    => ['required', 'string', 'in:' . implode(',', array_keys(config('contract_cancel_reasons', [])))],
            'cancel_reason_code' => ['required', 'string', 'in:' . implode(',', $allReasonCodes)],
            'cancel_note'        => ['required', 'string', 'max:2000'],
            'cancel_attachment'  => FileUploadRules::documentOptional(),
        ]);

        $guest = GuestApplication::query()
            ->when($companyId > 0, fn ($q) => $q->forCompany($companyId))
            ->where('id', (int) $data['guest_id'])
            ->firstOrFail();

        $currentStatus = $this->normalizeContractStatus((string) ($guest->contract_status ?? 'not_requested'));
        if (in_array($currentStatus, ['not_requested', 'cancelled'], true)) {
            return redirect()->route('manager.contract-template.show', [
                'guest_id' => (int) $guest->id,
                'q'        => (string) ($guest->converted_student_id ?: $guest->email),
            ])->withErrors(['contract' => "İptal işlemi için mevcut durum uygun değil: {$currentStatus}."]);
        }

        $attachmentPath = null;
        if ($request->hasFile('cancel_attachment')) {
            $file = $request->file('cancel_attachment');
            $ext  = strtolower((string) ($file->getClientOriginalExtension() ?: 'pdf'));
            $name = 'contract_cancel_' . now()->format('Ymd_His') . '.' . $ext;
            $attachmentPath = $file->storeAs("contract-cancellations/{$guest->id}", $name, 'public');
        }

        $guest->forceFill([
            'contract_status'                 => 'cancelled',
            'contract_cancel_category'        => trim((string) $data['cancel_category']),
            'contract_cancel_reason_code'     => trim((string) $data['cancel_reason_code']),
            'contract_cancel_note'            => trim((string) $data['cancel_note']),
            'contract_cancel_attachment_path' => $attachmentPath,
            'contract_cancelled_at'           => now(),
            'contract_cancelled_by'           => (string) optional($request->user())->email,
            'status_message'                  => 'Sözleşme manager tarafından iptal edildi.',
        ])->save();

        ContractAuditLog::log(
            guestApplicationId: (int) $guest->id,
            oldStatus: $currentStatus,
            newStatus: 'cancelled',
            changedBy: (string) optional($request->user())->email,
            note: trim((string) $data['cancel_note']),
            ip: $request->ip()
        );
        $this->eventLogService->log(
            eventType: 'manager_contract_cancelled',
            entityType: 'guest_application',
            entityId: (string) $guest->id,
            message: "Guest #{$guest->id} sözleşmesi iptal edildi. Kategori: {$data['cancel_category']}, Neden: {$data['cancel_reason_code']}.",
            meta: [
                'category'    => $data['cancel_category'],
                'reason_code' => $data['cancel_reason_code'],
                'note'        => $data['cancel_note'],
                'attachment'  => $attachmentPath,
            ],
            actorEmail: (string) optional($request->user())->email,
            companyId: (int) ($guest->company_id ?: 0)
        );
        $this->dispatchContractNotification($guest, 'guest_contract_update', 'manager_contract_cancelled');

        return redirect()->route('manager.contract-template.show', [
            'guest_id' => (int) $guest->id,
            'q'        => (string) ($guest->converted_student_id ?: $guest->email),
        ])->with('status', 'Sözleşme iptal edildi.');
    }

    public function approveReopen(Request $request): RedirectResponse
    {
        $companyId = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
        $data = $request->validate([
            'guest_id' => ['required', 'integer', 'min:1'],
        ]);

        $guest = GuestApplication::query()
            ->when($companyId > 0, fn ($q) => $q->forCompany($companyId))
            ->where('id', (int) $data['guest_id'])
            ->firstOrFail();

        $currentStatus = $this->normalizeContractStatus((string) ($guest->contract_status ?? 'not_requested'));
        if ($currentStatus !== 'reopen_requested') {
            return redirect()->route('manager.contract-template.show', [
                'guest_id' => (int) $guest->id,
                'q'        => (string) ($guest->converted_student_id ?: $guest->email),
            ])->withErrors(['contract' => "Onay için durum 'reopen_requested' olmalıdır. Mevcut: {$currentStatus}."]);
        }

        $actor = (string) optional($request->user())->email;

        $guest->forceFill([
            'contract_status'                 => 'not_requested',
            'contract_cancel_category'        => null,
            'contract_cancel_reason_code'     => null,
            'contract_cancel_note'            => null,
            'contract_cancel_attachment_path' => null,
            'contract_cancelled_at'           => null,
            'contract_cancelled_by'           => null,
            'reopen_decided_by'               => $actor,
            'reopen_decided_at'               => now(),
            'status_message'                  => 'Yeniden değerlendirme talebi kabul edildi. Sözleşme süreci sıfırlandı.',
        ])->save();

        ContractAuditLog::log(
            guestApplicationId: (int) $guest->id,
            oldStatus: 'reopen_requested',
            newStatus: 'not_requested',
            changedBy: $actor,
            note: 'Yeniden değerlendirme talebi kabul edildi.',
            ip: $request->ip()
        );
        $this->eventLogService->log(
            eventType: 'manager_contract_reopen_approved',
            entityType: 'guest_application',
            entityId: (string) $guest->id,
            message: "Guest #{$guest->id} yeniden değerlendirme talebi kabul edildi, süreç sıfırlandı.",
            meta: null,
            actorEmail: $actor,
            companyId: (int) ($guest->company_id ?: 0)
        );
        $this->dispatchContractNotification($guest, 'guest_contract_update', 'manager_contract_reopen_approved');

        return redirect()->route('manager.contract-template.show', [
            'guest_id' => (int) $guest->id,
            'q'        => (string) ($guest->converted_student_id ?: $guest->email),
        ])->with('status', 'Yeniden değerlendirme talebi kabul edildi. Süreç sıfırlandı.');
    }

    public function rejectReopen(Request $request): RedirectResponse
    {
        $companyId = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
        $data = $request->validate([
            'guest_id'    => ['required', 'integer', 'min:1'],
            'reject_note' => ['nullable', 'string', 'max:500'],
        ]);

        $guest = GuestApplication::query()
            ->when($companyId > 0, fn ($q) => $q->forCompany($companyId))
            ->where('id', (int) $data['guest_id'])
            ->firstOrFail();

        $currentStatus = $this->normalizeContractStatus((string) ($guest->contract_status ?? 'not_requested'));
        if ($currentStatus !== 'reopen_requested') {
            return redirect()->route('manager.contract-template.show', [
                'guest_id' => (int) $guest->id,
                'q'        => (string) ($guest->converted_student_id ?: $guest->email),
            ])->withErrors(['contract' => "Red için durum 'reopen_requested' olmalıdır. Mevcut: {$currentStatus}."]);
        }

        $actor     = (string) optional($request->user())->email;
        $rejectMsg = trim((string) ($data['reject_note'] ?? ''));

        $guest->forceFill([
            'contract_status'   => 'cancelled',
            'reopen_decided_by' => $actor,
            'reopen_decided_at' => now(),
            'status_message'    => $rejectMsg !== '' ? $rejectMsg : 'Yeniden değerlendirme talebi reddedildi.',
        ])->save();

        ContractAuditLog::log(
            guestApplicationId: (int) $guest->id,
            oldStatus: 'reopen_requested',
            newStatus: 'cancelled',
            changedBy: $actor,
            note: $rejectMsg !== '' ? $rejectMsg : null,
            ip: $request->ip()
        );
        $this->eventLogService->log(
            eventType: 'manager_contract_reopen_rejected',
            entityType: 'guest_application',
            entityId: (string) $guest->id,
            message: "Guest #{$guest->id} yeniden değerlendirme talebi reddedildi.",
            meta: ['reject_note' => $rejectMsg],
            actorEmail: $actor,
            companyId: (int) ($guest->company_id ?: 0)
        );
        $this->dispatchContractNotification($guest, 'guest_contract_update', 'manager_contract_reopen_rejected');

        return redirect()->route('manager.contract-template.show', [
            'guest_id' => (int) $guest->id,
            'q'        => (string) ($guest->converted_student_id ?: $guest->email),
        ])->with('status', 'Yeniden değerlendirme talebi reddedildi.');
    }

    public function resetContract(Request $request): RedirectResponse
    {
        $companyId = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;
        $data = $request->validate([
            'guest_id' => ['required', 'integer', 'min:1'],
        ]);

        $guest = GuestApplication::query()
            ->when($companyId > 0, fn ($q) => $q->forCompany($companyId))
            ->where('id', (int) $data['guest_id'])
            ->firstOrFail();

        $currentStatus = $this->normalizeContractStatus((string) ($guest->contract_status ?? 'not_requested'));
        if (! in_array($currentStatus, ['cancelled', 'reopen_requested'], true)) {
            return redirect()->route('manager.contract-template.show', [
                'guest_id' => (int) $guest->id,
                'q'        => (string) ($guest->converted_student_id ?: $guest->email),
            ])->withErrors(['contract' => "Sıfırlama için durum 'cancelled' veya 'reopen_requested' olmalıdır. Mevcut: {$currentStatus}."]);
        }

        $actor = (string) optional($request->user())->email;

        $guest->forceFill([
            'contract_status'                 => 'not_requested',
            'contract_cancel_category'        => null,
            'contract_cancel_reason_code'     => null,
            'contract_cancel_note'            => null,
            'contract_cancel_attachment_path' => null,
            'contract_cancelled_at'           => null,
            'contract_cancelled_by'           => null,
            'reopen_reason'                   => null,
            'reopen_requested_at'             => null,
            'reopen_decided_by'               => $actor,
            'reopen_decided_at'               => now(),
            'status_message'                  => 'Sözleşme süreci manager tarafından sıfırlandı.',
        ])->save();

        ContractAuditLog::log(
            guestApplicationId: (int) $guest->id,
            oldStatus: $currentStatus,
            newStatus: 'not_requested',
            changedBy: $actor,
            note: 'Süreç sıfırlandı.',
            ip: $request->ip()
        );
        $this->eventLogService->log(
            eventType: 'manager_contract_reset',
            entityType: 'guest_application',
            entityId: (string) $guest->id,
            message: "Guest #{$guest->id} sözleşme süreci manager tarafından sıfırlandı.",
            meta: null,
            actorEmail: $actor,
            companyId: (int) ($guest->company_id ?: 0)
        );
        $this->dispatchContractNotification($guest, 'guest_contract_update', 'manager_contract_reset');

        return redirect()->route('manager.contract-template.show', [
            'guest_id' => (int) $guest->id,
            'q'        => (string) ($guest->converted_student_id ?: $guest->email),
        ])->with('status', 'Sözleşme süreci sıfırlandı. Misafir yeniden talep oluşturabilir.');
    }

    public function batchDecision(Request $request): JsonResponse
    {
        $companyId = app()->bound('current_company_id') ? (int) app('current_company_id') : 0;

        $data = $request->validate([
            'guest_ids'   => ['required', 'array', 'min:1', 'max:50'],
            'guest_ids.*' => ['required', 'integer', 'min:1'],
            'decision'    => ['required', 'in:approve,reject'],
            'note'        => ['nullable', 'string', 'max:500'],
        ]);

        $decision  = (string) $data['decision'];
        $note      = trim((string) ($data['note'] ?? ''));
        $actor     = (string) optional($request->user())->email;
        $processed = 0;
        $skipped   = 0;
        $errors    = [];

        foreach ((array) $data['guest_ids'] as $guestId) {
            $guest = GuestApplication::query()
                ->when($companyId > 0, fn ($q) => $q->forCompany($companyId))
                ->where('id', (int) $guestId)
                ->first();

            if (! $guest) {
                $errors[] = "Guest #{$guestId} bulunamadı.";
                $skipped++;
                continue;
            }

            $currentStatus = $this->normalizeContractStatus((string) ($guest->contract_status ?? 'not_requested'));

            if ($currentStatus !== 'signed_uploaded') {
                $skipped++;
                $errors[] = "Guest #{$guestId}: Beklenen durum 'signed_uploaded'. Mevcut: {$currentStatus}.";
                continue;
            }

            if ($this->contractStateHasInconsistency($guest, $currentStatus)) {
                $skipped++;
                $errors[] = "Guest #{$guestId}: Sözleşme kaydında tutarsızlık.";
                continue;
            }

            if ($decision === 'approve') {
                if (trim((string) ($guest->contract_signed_file_path ?? '')) === '' && empty($guest->contract_digital_signed_at)) {
                    $skipped++;
                    $errors[] = "Guest #{$guestId}: İmzalı sözleşme yüklenmemiş.";
                    continue;
                }

                $newStudentId = trim((string) ($guest->converted_student_id ?? ''));
                if ($newStudentId === '') {
                    $newStudentId = 'STU-' . str_pad((string) $guest->id, 8, '0', STR_PAD_LEFT);
                }

                $guest->forceFill([
                    'contract_status'      => 'approved',
                    'contract_approved_at' => now(),
                    'converted_to_student' => true,
                    'converted_student_id' => $newStudentId,
                    'lead_status'          => 'converted',
                    'status_message'       => $note !== '' ? "Toplu onay: {$note}" : 'Toplu onay ile sözleşme onaylandı.',
                ])->save();

                User::query()
                    ->where('email', strtolower((string) $guest->email))
                    ->where('role', User::ROLE_GUEST)
                    ->update(['role' => User::ROLE_STUDENT]);

                ContractAuditLog::log(
                    guestApplicationId: (int) $guest->id,
                    oldStatus: 'signed_uploaded',
                    newStatus: 'approved',
                    changedBy: $actor,
                    note: $note !== '' ? "[Toplu] {$note}" : '[Toplu onay]',
                    ip: $request->ip()
                );
                $this->dispatchContractNotification($guest, 'guest_contract_update', 'manager_contract_approved');
            } else {
                $guest->forceFill([
                    'contract_status'      => 'rejected',
                    'contract_approved_at' => null,
                    'status_message'       => $note !== '' ? "Toplu ret: {$note}" : 'Toplu ret ile sözleşme reddedildi.',
                ])->save();

                ContractAuditLog::log(
                    guestApplicationId: (int) $guest->id,
                    oldStatus: 'signed_uploaded',
                    newStatus: 'rejected',
                    changedBy: $actor,
                    note: $note !== '' ? "[Toplu] {$note}" : '[Toplu ret]',
                    ip: $request->ip()
                );
                $this->dispatchContractNotification($guest, 'guest_contract_update', 'manager_contract_rejected');
            }

            $processed++;
        }

        return response()->json([
            'processed' => $processed,
            'skipped'   => $skipped,
            'errors'    => $errors,
        ]);
    }
}
