<?php

namespace App\Http\Controllers\Manager\Concerns;

use App\Models\GuestApplication;
use App\Services\NotificationService;

trait ContractHelperTrait
{
    protected function normalizeContractStatus(string $status): string
    {
        $normalized = strtolower(trim($status));

        return in_array($normalized, [
            'not_requested', 'pending_manager', 'requested', 'signed_uploaded',
            'approved', 'rejected', 'cancelled', 'reopen_requested',
        ], true) ? $normalized : 'not_requested';
    }

    protected function contractStateHasInconsistency(GuestApplication $guest, string $status): bool
    {
        $hasSnapshot    = trim((string) ($guest->contract_snapshot_text ?? '')) !== '';
        $hasTemplate    = trim((string) ($guest->contract_template_code ?? '')) !== '' || ! empty($guest->contract_template_id);
        $hasRequestedAt = ! empty($guest->contract_requested_at);
        $hasSignedFile  = trim((string) ($guest->contract_signed_file_path ?? '')) !== '';
        $hasSignedAt    = ! empty($guest->contract_signed_at);
        $hasApprovedAt  = ! empty($guest->contract_approved_at);

        if (in_array($status, ['requested', 'signed_uploaded', 'approved', 'rejected'], true)
            && (! $hasSnapshot || ! $hasTemplate || ! $hasRequestedAt)) {
            return true;
        }
        if (in_array($status, ['signed_uploaded', 'approved'], true) && (! $hasSignedFile || ! $hasSignedAt)) {
            return true;
        }
        if ($status === 'approved' && ! $hasApprovedAt) {
            return true;
        }

        return false;
    }

    protected function dispatchContractNotification(GuestApplication $guest, string $category, string $sourceType): void
    {
        /** @var NotificationService $notificationService */
        $notificationService = $this->notificationService;

        $studentId = trim((string) ($guest->converted_student_id ?? ''));
        if ($studentId === '') {
            $studentId = 'GST-' . str_pad((string) $guest->id, 8, '0', STR_PAD_LEFT);
        }

        $notificationService->send([
            'channel'     => 'in_app',
            'category'    => $category,
            'student_id'  => $studentId,
            'company_id'  => (int) ($guest->company_id ?: 0),
            'body'        => 'Sözleşme sürecinde güncelleme: ' . $sourceType,
            'source_type' => 'guest_application',
            'source_id'   => (string) $guest->id,
        ]);
    }
}
