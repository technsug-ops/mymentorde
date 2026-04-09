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
        $hasDigitalSign = ! empty($guest->contract_digital_signed_at);
        $hasAnySigning  = $hasSignedFile || $hasSignedAt || $hasDigitalSign;
        $hasApprovedAt  = ! empty($guest->contract_approved_at);

        if (in_array($status, ['requested', 'signed_uploaded', 'approved', 'rejected'], true)
            && (! $hasSnapshot || ! $hasTemplate || ! $hasRequestedAt)) {
            return true;
        }
        if (in_array($status, ['signed_uploaded', 'approved'], true) && ! $hasAnySigning) {
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

        // Kullanici ID'sini bul
        $userId = null;
        if ($guest->guest_user_id) {
            $userId = (int) $guest->guest_user_id;
        } elseif ($studentId !== '' && !str_starts_with($studentId, 'GST-')) {
            $userId = \App\Models\User::where('student_id', $studentId)->value('id');
        }

        $messages = [
            'manager_contract_started'          => ['subject' => 'Sozlesmeniz hazir', 'body' => 'Danismaniniz sozlesmenizi hazirladi. Lutfen sozlesme sayfanizi kontrol edin, okuyun ve imzalayin.'],
            'manager_contract_approved'         => ['subject' => 'Sozlesmeniz onaylandi', 'body' => 'Tebrikler! Imzali sozlesmeniz onaylandi. Artik resmi ogrencisiniz.'],
            'manager_contract_rejected'         => ['subject' => 'Sozlesmeniz reddedildi', 'body' => 'Imzali sozlesmeniz reddedildi. Lutfen sozlesme sayfanizi kontrol edip tekrar gonderin.'],
            'manager_contract_cancelled'        => ['subject' => 'Sozlesmeniz iptal edildi', 'body' => 'Sozlesmeniz iptal edilmistir. Detaylar icin sozlesme sayfanizi kontrol edin.'],
            'manager_contract_reopen_approved'  => ['subject' => 'Yeniden degerlendirme onaylandi', 'body' => 'Sozlesme yeniden degerlendirme talebiniz onaylandi.'],
            'manager_contract_reopen_rejected'  => ['subject' => 'Yeniden degerlendirme reddedildi', 'body' => 'Sozlesme yeniden degerlendirme talebiniz reddedildi.'],
            'manager_contract_reset'            => ['subject' => 'Sozlesme sifirlandi', 'body' => 'Sozlesmeniz sifirlanmistir. Lutfen sozlesme sayfanizi kontrol edin.'],
        ];

        $msg = $messages[$sourceType] ?? ['subject' => 'Sozlesme guncelleme', 'body' => 'Sozlesme surecinde guncelleme var.'];

        // In-app bildirim
        $notificationService->send([
            'channel'     => 'in_app',
            'category'    => $category,
            'user_id'     => $userId,
            'student_id'  => $studentId,
            'company_id'  => (int) ($guest->company_id ?: 0),
            'subject'     => $msg['subject'],
            'body'        => $msg['body'],
            'source_type' => 'guest_application',
            'source_id'   => (string) $guest->id,
        ]);

        // E-posta bildirimi
        $email = trim((string) ($guest->email ?? ''));
        if ($email !== '') {
            $notificationService->send([
                'channel'         => 'email',
                'category'        => $category,
                'user_id'         => $userId,
                'student_id'      => $studentId,
                'company_id'      => (int) ($guest->company_id ?: 0),
                'recipient_email' => $email,
                'subject'         => $msg['subject'],
                'body'            => $msg['body'],
                'source_type'     => 'guest_application',
                'source_id'       => (string) $guest->id,
            ]);
        }
    }
}
