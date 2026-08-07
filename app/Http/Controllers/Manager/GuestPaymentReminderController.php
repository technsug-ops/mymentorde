<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Mail\PaymentReceivedMail;
use App\Mail\PaymentReminderMail;
use App\Models\GuestApplication;
use App\Models\GuestPaymentRequest;
use App\Services\EventLogService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

/**
 * Manager — sözleşme onaylanmış ama ödeme bekleyen aday/öğrencilerin
 * hatırlatma akışını yönetir.
 *
 * Otomatik (cron L1..L4) + manuel (manager butonu — şimdi gönder, pause/resume,
 * Level 5 iptal uyarısı, manuel ödeme alındı işaretle).
 *
 * NOT: İleride dedicated bir finans admin rolü açılırsa bu controller
 * Finance namespace'ine taşınabilir; route name'leri "manager." prefix'i ile
 * mevcut hub içinde olduğu için tek noktada değişir.
 * Ayrıca bkz. memory/project_finance_admin_role_pending.md
 */
class GuestPaymentReminderController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly EventLogService $eventLogService,
    ) {}

    /**
     * Sözleşme onaylanmış + ödeme bekleyen adayların listesi.
     */
    public function index(Request $request): View
    {
        $rows = GuestApplication::query()
            ->whereNotNull('contract_approved_at')
            ->whereNull('payment_received_at')
            ->orderByDesc('contract_approved_at')
            ->limit(200)
            ->get();

        $bankInfo = (array) config('brand.banking', []);
        $reminderDays = (array) config('brand.payment_reminder_days', [1 => 7, 2 => 14, 3 => 21, 4 => 28]);
        $finalGraceDays = (int) config('brand.payment_final_grace_days', 15);

        return view('manager.payments.reminders', [
            'rows'           => $rows,
            'bankInfo'       => $bankInfo,
            'reminderDays'   => $reminderDays,
            'finalGraceDays' => $finalGraceDays,
        ]);
    }

    /**
     * Manuel olarak hatırlatma gönder (level body param ile).
     * Level 1..5 destekler — Level 5 modal teyit ile gönderilir.
     */
    public function sendReminder(Request $request, GuestApplication $guest): RedirectResponse
    {
        $data = $request->validate([
            'level' => 'required|integer|min:1|max:5',
        ]);
        $level = (int) $data['level'];

        if (! $guest->contract_approved_at) {
            return back()->withErrors(['payment' => 'Bu sözleşme henüz onaylanmamış.']);
        }
        if ($guest->payment_received_at) {
            return back()->withErrors(['payment' => 'Bu adayın ödemesi zaten alınmış.']);
        }

        $bankInfo = (array) config('brand.banking', []);
        if (empty($bankInfo['iban'] ?? '')) {
            return back()->withErrors(['payment' => 'Banka bilgisi (IBAN) yapılandırılmamış. Önce ayarlardan ekleyin.']);
        }

        $email = trim((string) ($guest->email ?? ''));
        if ($email === '') {
            return back()->withErrors(['payment' => 'Adayın e-posta adresi yok.']);
        }

        $amount = $this->resolveAmount($guest);
        if ($amount === null || $amount <= 0) {
            return back()->withErrors(['payment' => 'Ödeme tutarı belirlenemedi.']);
        }

        $currency = (string) ($bankInfo['currency'] ?? 'EUR');
        $paymentAmountText = number_format($amount, 0, ',', '.') . ' ' . $currency;

        $studentRef = trim((string) ($guest->converted_student_id ?? ''));
        if ($studentRef === '') {
            $studentRef = 'GST-' . str_pad((string) $guest->id, 8, '0', STR_PAD_LEFT);
        }
        $fullName = trim(((string) ($guest->first_name ?? '')) . ' ' . ((string) ($guest->last_name ?? '')));
        $paymentReference = mb_strtoupper($fullName !== '' ? $fullName : 'OGRENCI', 'UTF-8') . ' #' . $studentRef;

        $contractTitle = trim((string) ($guest->contract_template_code ?? '')) ?: 'Öğrenci Eğitim Sözleşmesi';
        $contractNo    = trim((string) ($guest->tracking_token ?? '')) ?: null;
        $portalUrl     = url('/guest/contract');
        $finalGraceDays = (int) config('brand.payment_final_grace_days', 15);
        $daysSince = $guest->contract_approved_at instanceof Carbon
            ? (int) $guest->contract_approved_at->diffInDays(now())
            : 0;

        try {
            Mail::to($email)->queue(new PaymentReminderMail(
                recipientName: $fullName !== '' ? $fullName : 'Sayın Öğrencimiz',
                contractTitle: $contractTitle,
                contractNo: $contractNo,
                paymentAmountText: $paymentAmountText,
                paymentReference: $paymentReference,
                bankInfo: $bankInfo,
                portalUrl: $portalUrl,
                level: $level,
                daysOverdue: $daysSince,
                finalGraceDays: $finalGraceDays,
            ));

            $guest->forceFill([
                'payment_reminder_level'        => max((int) $guest->payment_reminder_level, $level),
                'payment_reminder_last_sent_at' => now(),
            ])->save();

            $this->eventLogService->log(
                eventType: 'guest_payment_reminder_sent',
                entityType: 'guest_application',
                entityId: (string) $guest->id,
                message: "Manager #" . (Auth::id() ?? '?') . " manuel olarak L{$level} ödeme hatırlatması gönderdi.",
                meta: ['level' => $level, 'email' => $email],
            );

            return back()->with('success', "L{$level} hatırlatması gönderildi.");
        } catch (\Throwable $e) {
            Log::warning('payment.reminder.manual.failed', [
                'guest_id' => $guest->id,
                'level'    => $level,
                'error'    => $e->getMessage(),
            ]);
            return back()->withErrors(['payment' => 'Mail gönderilemedi: ' . $e->getMessage()]);
        }
    }

    /**
     * Hatırlatma akışını duraklat (banka sorunu, mücbir sebep vb.)
     */
    public function pause(Request $request, GuestApplication $guest): RedirectResponse
    {
        $data = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $guest->forceFill([
            'payment_reminders_paused_at'     => now(),
            'payment_reminders_paused_reason' => $data['reason'],
            'payment_reminders_paused_by'     => Auth::id(),
        ])->save();

        $this->eventLogService->log(
            eventType: 'guest_payment_reminder_paused',
            entityType: 'guest_application',
            entityId: (string) $guest->id,
            message: "Manager #" . (Auth::id() ?? '?') . " hatırlatma akışını duraklattı.",
            meta: ['reason' => $data['reason']],
        );

        return back()->with('success', 'Hatırlatma akışı duraklatıldı.');
    }

    /**
     * Duraklatılmış hatırlatma akışını devam ettir.
     */
    public function resume(GuestApplication $guest): RedirectResponse
    {
        $guest->forceFill([
            'payment_reminders_paused_at'     => null,
            'payment_reminders_paused_reason' => null,
            'payment_reminders_paused_by'     => null,
        ])->save();

        $this->eventLogService->log(
            eventType: 'guest_payment_reminder_resumed',
            entityType: 'guest_application',
            entityId: (string) $guest->id,
            message: "Manager #" . (Auth::id() ?? '?') . " hatırlatma akışını devam ettirdi.",
        );

        return back()->with('success', 'Hatırlatma akışı yeniden açıldı.');
    }

    /**
     * Manager manuel olarak ödemeyi teyit eder.
     * - guest_applications.payment_received_at + by + notes set
     * - Mevcut GuestPaymentRequest kayıtlarını 'paid' olarak işaretle
     * - Adaya PaymentReceivedMail gönder
     */
    public function markReceived(Request $request, GuestApplication $guest): RedirectResponse
    {
        $data = $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($guest->payment_received_at) {
            return back()->withErrors(['payment' => 'Bu ödeme zaten teyit edilmiş.']);
        }

        $now = now();
        $guest->forceFill([
            'payment_received_at'    => $now,
            'payment_received_by'    => Auth::id(),
            'payment_received_notes' => $data['notes'] ?? null,
        ])->save();

        // Mevcut GuestPaymentRequest kayıtlarını paid olarak işaretle
        GuestPaymentRequest::query()
            ->where('guest_application_id', $guest->id)
            ->where('status', '!=', 'paid')
            ->update([
                'status'  => 'paid',
                'paid_at' => $now,
            ]);

        // Adaya bildir
        $email = trim((string) ($guest->email ?? ''));
        if ($email !== '') {
            $fullName = trim(((string) ($guest->first_name ?? '')) . ' ' . ((string) ($guest->last_name ?? '')));
            $contractTitle = trim((string) ($guest->contract_template_code ?? '')) ?: 'Öğrenci Eğitim Sözleşmesi';
            $contractNo    = trim((string) ($guest->tracking_token ?? '')) ?: null;

            $amount = $this->resolveAmount($guest);
            $bankInfo = (array) config('brand.banking', []);
            $currency = (string) ($bankInfo['currency'] ?? 'EUR');
            $paymentAmountText = ($amount && $amount > 0)
                ? number_format($amount, 0, ',', '.') . ' ' . $currency
                : null;

            // Atanmış danışman adı (varsa)
            $advisorName = null;
            if (! empty($guest->assigned_senior_email)) {
                $advisorName = \App\Models\User::where('email', $guest->assigned_senior_email)
                    ->value('name');
            }

            try {
                Mail::to($email)->queue(new PaymentReceivedMail(
                    recipientName: $fullName !== '' ? $fullName : 'Sayın Öğrencimiz',
                    contractTitle: $contractTitle,
                    contractNo: $contractNo,
                    paymentAmountText: $paymentAmountText,
                    paymentDateText: $now->format('d.m.Y'),
                    portalUrl: url('/guest/contract'),
                    advisorName: $advisorName,
                ));
            } catch (\Throwable $e) {
                Log::warning('payment.received.mail.failed', [
                    'guest_id' => $guest->id,
                    'error'    => $e->getMessage(),
                ]);
            }
        }

        $this->eventLogService->log(
            eventType: 'guest_payment_marked_received',
            entityType: 'guest_application',
            entityId: (string) $guest->id,
            message: "Manager #" . (Auth::id() ?? '?') . " ödemeyi manuel teyit etti.",
            meta: ['notes' => $data['notes'] ?? null],
        );

        return back()->with('success', 'Ödeme teyit edildi ve adaya bildirim gönderildi.');
    }

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

        return \App\Support\ServiceCatalog::quote(
            $selCode,
            $guest->selected_extra_services,
            (int) ($guest->company_id ?? 0)
        );
    }
}
