<?php

namespace App\Console\Commands;

use App\Mail\PaymentReminderMail;
use App\Models\GuestApplication;
use App\Models\GuestPaymentRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Sözleşme onaylanmış ama ödeme yapılmamış adaylara kademeli hatırlatma.
 *
 * Otomatik olarak L1..L4 gönderir; L5 (iptal uyarısı) manuel manager butonu ile.
 * Aralıklar config('brand.payment_reminder_days') üzerinden — varsayılan 7/14/21/28.
 *
 * Filtreler:
 *  - contract_approved_at NOT NULL
 *  - payment_received_at NULL (manuel teyit yok)
 *  - payment_reminders_paused_at NULL (manager duraklatmadı)
 *  - GuestPaymentRequest status='paid' yoksa (ödeme yapılmadı)
 *  - payment_reminder_level < 4 (L5 manuel)
 *  - contract_approved_at + N gün şartı sağlanıyor
 */
class PaymentReminderCommand extends Command
{
    protected $signature = 'payments:send-reminders
                            {--dry-run : Sadece raporla, gönderme}';

    protected $description = 'Sözleşme onaylı ama ödeme yapmamış adaylara kademeli hatırlatma gönder (L1-L4 oto, L5 manuel)';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $reminderDays = (array) config('brand.payment_reminder_days', [1 => 7, 2 => 14, 3 => 21, 4 => 28]);
        $bankInfo = (array) config('brand.banking', []);
        $finalGraceDays = (int) config('brand.payment_final_grace_days', 15);

        if (empty($bankInfo['iban'] ?? '')) {
            $this->warn('IBAN config eksik (BRAND_BANK_IBAN) — hatırlatma gönderilmeyecek.');
            return Command::SUCCESS;
        }

        // Adayları seç: onaylı sözleşme + ödeme alınmadı + duraklatılmadı + L5'e gelmemiş
        $candidates = GuestApplication::query()
            ->whereNotNull('contract_approved_at')
            ->whereNull('payment_received_at')
            ->whereNull('payment_reminders_paused_at')
            ->where('payment_reminder_level', '<', 4)
            ->whereNotNull('email')
            ->get();

        $sent = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($candidates as $guest) {
            $approvedAt = $guest->contract_approved_at;
            if (! $approvedAt instanceof Carbon) {
                $skipped++;
                continue;
            }

            $daysSince = (int) $approvedAt->diffInDays(now());

            // Ödeme yapıldıysa atla (GuestPaymentRequest tablosu üzerinden)
            $hasPaid = GuestPaymentRequest::query()
                ->where('guest_application_id', $guest->id)
                ->where('status', 'paid')
                ->exists();
            if ($hasPaid) {
                $skipped++;
                continue;
            }

            // Hangi seviyeye gelinmeli?
            $currentLevel = (int) ($guest->payment_reminder_level ?? 0);
            $nextLevel = null;
            for ($lvl = 1; $lvl <= 4; $lvl++) {
                $threshold = (int) ($reminderDays[$lvl] ?? 0);
                if ($threshold <= 0) continue;
                if ($daysSince >= $threshold && $currentLevel < $lvl) {
                    $nextLevel = $lvl;
                }
            }

            if ($nextLevel === null) {
                $skipped++;
                continue;
            }

            // Tutarı belirle (GuestPaymentRequest > paket+ekstra)
            $amount = $this->resolveAmount($guest);
            if ($amount === null || $amount <= 0) {
                $this->warn("Guest #{$guest->id}: tutar belirlenemedi, atlanıyor.");
                $skipped++;
                continue;
            }
            $currency = (string) ($bankInfo['currency'] ?? 'EUR');
            $paymentAmountText = number_format($amount, 0, ',', '.') . ' ' . $currency;

            // Açıklama referansı
            $studentRef = trim((string) ($guest->converted_student_id ?? ''));
            if ($studentRef === '') {
                $studentRef = 'GST-' . str_pad((string) $guest->id, 8, '0', STR_PAD_LEFT);
            }
            $fullName = trim(((string) ($guest->first_name ?? '')) . ' ' . ((string) ($guest->last_name ?? '')));
            $paymentReference = mb_strtoupper($fullName !== '' ? $fullName : 'OGRENCI', 'UTF-8') . ' #' . $studentRef;

            $recipientName = $fullName !== '' ? $fullName : 'Sayın Öğrencimiz';
            $contractTitle = trim((string) ($guest->contract_template_code ?? '')) ?: 'Öğrenci Eğitim Sözleşmesi';
            $contractNo    = trim((string) ($guest->tracking_token ?? '')) ?: null;
            $portalUrl     = url('/guest/contract');

            $this->line("Guest #{$guest->id} ({$guest->email}) → L{$nextLevel} (gün: {$daysSince})");

            if ($dryRun) {
                $sent++;
                continue;
            }

            try {
                Mail::to((string) $guest->email)->queue(new PaymentReminderMail(
                    recipientName: $recipientName,
                    contractTitle: $contractTitle,
                    contractNo: $contractNo,
                    paymentAmountText: $paymentAmountText,
                    paymentReference: $paymentReference,
                    bankInfo: $bankInfo,
                    portalUrl: $portalUrl,
                    level: $nextLevel,
                    daysOverdue: $daysSince,
                    finalGraceDays: $finalGraceDays,
                ));

                $guest->forceFill([
                    'payment_reminder_level' => $nextLevel,
                    'payment_reminder_last_sent_at' => now(),
                ])->save();

                $sent++;
            } catch (\Throwable $e) {
                $errors++;
                Log::warning('payment.reminder.failed', [
                    'guest_id' => $guest->id,
                    'level'    => $nextLevel,
                    'error'    => $e->getMessage(),
                ]);
                $this->error("Guest #{$guest->id} L{$nextLevel} gönderilemedi: {$e->getMessage()}");
            }
        }

        $tag = $dryRun ? '[dry-run]' : '';
        $this->info("{$tag} Gönderildi: {$sent} | Atlandı: {$skipped} | Hata: {$errors}");

        return Command::SUCCESS;
    }

    /**
     * Önce GuestPaymentRequest, yoksa paket + ek hizmetler toplamı.
     */
    private function resolveAmount(GuestApplication $guest): ?float
    {
        // Pazarlık sonucu sabitlenmiş tutar varsa her şeyin önünde gelir.
        if ($guest->contract_amount_locked_at && (float) $guest->contract_amount_eur > 0) {
            return (float) $guest->contract_amount_eur;
        }

        $latest = GuestPaymentRequest::query()
            ->where('guest_application_id', $guest->id)
            ->orderByDesc('id')
            ->first();
        if ($latest && (float) $latest->amount_eur > 0) {
            return (float) $latest->amount_eur;
        }

        $selCode = (string) ($guest->selected_package_code ?? '');
        if ($selCode === '') return null;

        // Komut arka planda çalışıyor: istek bağlamı yok, firma adaydan alınmalı.
        return \App\Support\ServiceCatalog::quote(
            $selCode,
            $guest->selected_extra_services,
            (int) ($guest->company_id ?? 0)
        );
    }
}
