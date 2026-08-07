<?php

namespace App\Http\Controllers\Manager\Concerns;

use App\Mail\ContractCompletedMail;
use App\Models\GuestApplication;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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
            // manager_contract_approved → zengin mail (PDF + ek maddeler) gönder.
            // Diğer event'ler için mevcut basit notification yeter.
            if ($sourceType === 'manager_contract_approved') {
                $this->sendContractCompletedMail($guest);
            } else {
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

    /**
     * Sözleşme onaylandığında öğrenciye zengin mail gönder:
     * imzalı sözleşme PDF'i + varsa ek maddeler. Panel bildirimi yine yapılır,
     * bu sadece e-posta tarafıdır.
     */
    private function sendContractCompletedMail(GuestApplication $guest): void
    {
        $email = trim((string) ($guest->email ?? ''));
        if ($email === '') return;

        $recipient = trim((string) ($guest->first_name ?? '') . ' ' . (string) ($guest->last_name ?? ''));
        if ($recipient === '') $recipient = 'Sayın Öğrencimiz';

        // İmzalı sözleşme PDF
        $attachments = [];
        $signedPath = trim((string) ($guest->contract_signed_file_path ?? ''));
        if ($signedPath !== '') {
            $attachments[] = $signedPath;
        }

        // Ek annex dosyaları (contract_annex_files JSON kolonu varsa)
        $annexFiles = $guest->contract_annex_files ?? null;
        if (is_array($annexFiles)) {
            foreach ($annexFiles as $f) {
                $path = is_array($f) ? trim((string) ($f['path'] ?? '')) : trim((string) $f);
                if ($path !== '') $attachments[] = $path;
            }
        }

        // Ek metin notları (contract_annex_text JSON kolonu varsa)
        $annexNotes = [];
        $annexText = $guest->contract_annex_text ?? null;
        if (is_array($annexText)) {
            foreach ($annexText as $t) {
                $note = is_array($t) ? (string) ($t['note'] ?? '') : (string) $t;
                $note = trim($note);
                if ($note !== '') $annexNotes[] = $note;
            }
        } elseif (is_string($annexText) && trim($annexText) !== '') {
            $annexNotes[] = trim($annexText);
        }

        $contractTitle = trim((string) ($guest->contract_template_code ?? '')) ?: 'Öğrenci Eğitim Sözleşmesi';
        $contractNo    = trim((string) ($guest->tracking_token ?? ''));
        $portalUrl     = url('/guest/contract');

        // ── Ödeme bilgisi (sözleşme onayında zorunlu havale yönlendirmesi) ──
        // Tutar önceliği — yukarıdan aşağıya, ilk bulunan kazanır:
        //   1) Sözleşmede elle sabitlenmiş tutar (pazarlık sonucu; finans da bunu alır)
        //   2) Daha önce oluşturulmuş ödeme talebi
        //   3) Katalogdan paket + ek hizmetler toplamı
        $bankInfo = (array) config('brand.banking', []);
        $paymentAmountText = null;
        $paymentReference  = null;
        if (! empty($bankInfo['iban'] ?? '')) {
            $currency = (string) ($bankInfo['currency'] ?? 'EUR');

            $amount = null;

            // Sabitlenmemiş tutar taslak sayılır; sözleşme metnine yazılmaz.
            if ($guest->contract_amount_locked_at && (float) $guest->contract_amount_eur > 0) {
                $amount = (float) $guest->contract_amount_eur;
            }

            if ($amount === null) {
                $latestPayment = \App\Models\GuestPaymentRequest::query()
                    ->where('guest_application_id', $guest->id)
                    ->orderByDesc('id')
                    ->first();

                $amount = $latestPayment ? (float) $latestPayment->amount_eur : null;
            }

            if ($amount === null) {
                $selCode = (string) ($guest->selected_package_code ?? '');
                if ($selCode !== '') {
                    $amount = \App\Support\ServiceCatalog::quote(
                        $selCode,
                        $guest->selected_extra_services,
                        (int) ($guest->company_id ?? 0)
                    );
                }
            }

            if ($amount !== null && $amount > 0) {
                $paymentAmountText = number_format($amount, 0, ',', '.') . ' ' . $currency;

                // Açıklama: "AD SOYAD #ID" — ödeme eşleştirmede kişi karışıklığı önlemek için
                $studentRef = trim((string) ($guest->converted_student_id ?? ''));
                if ($studentRef === '') {
                    $studentRef = 'GST-' . str_pad((string) $guest->id, 8, '0', STR_PAD_LEFT);
                }
                $fullName = trim(((string) ($guest->first_name ?? '')) . ' ' . ((string) ($guest->last_name ?? '')));
                $paymentReference = mb_strtoupper($fullName !== '' ? $fullName : 'ÖĞRENCİ', 'UTF-8') . ' #' . $studentRef;
            }
        }

        try {
            Mail::to($email)->queue(new ContractCompletedMail(
                recipientName: $recipient,
                contractTitle: $contractTitle,
                contractNo: $contractNo ?: null,
                attachmentPaths: $attachments,
                annexNotes: $annexNotes,
                portalUrl: $portalUrl,
                paymentAmountText: $paymentAmountText,
                paymentReference: $paymentReference,
                bankInfo: $bankInfo,
                paymentDueDays: (int) config('brand.payment_due_days', 14),
            ));
        } catch (\Throwable $e) {
            Log::warning('contract.completed.mail.failed', [
                'guest_id' => $guest->id,
                'email'    => $email,
                'error'    => $e->getMessage(),
            ]);
        }
    }
}
