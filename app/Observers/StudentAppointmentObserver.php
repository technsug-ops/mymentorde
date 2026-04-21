<?php

namespace App\Observers;

use App\Models\StudentAppointment;
use App\Services\GoogleCalendarService;
use Illuminate\Support\Facades\Log;

/**
 * StudentAppointment model olayları — Google Calendar'a auto-sync.
 *
 * Push otomatik: created, updated (status değişmedi de, scheduled_at/title/note değişirse)
 * Soft-delete/cancel: event silinir veya status'u cancelled olarak güncellenir.
 *
 * Hata durumunda observer ORIGINAL işlemi bloklamaz — sadece log'lar.
 */
class StudentAppointmentObserver
{
    public function __construct(private GoogleCalendarService $calendar) {}

    public function saved(StudentAppointment $apt): void
    {
        // Sadece scheduled olanları push et — 'requested' henüz onaylanmadı
        if (empty($apt->scheduled_at)) return;
        if (in_array($apt->status, ['cancelled'], true)) {
            // Status "cancelled" olarak değişti → hem Google Calendar'dan sil
            // hem bağlı public_booking varsa invitee'ye mail gönder
            $this->handleCancellation($apt);
            $this->deleted($apt);
            return;
        }

        try {
            $this->calendar->pushAppointment($apt);
        } catch (\Throwable $e) {
            Log::warning('Google Calendar push observer hatası', [
                'appointment_id' => $apt->id,
                'error'          => $e->getMessage(),
            ]);
        }
    }

    /**
     * Public booking'den gelen bir randevu iptal edildiğinde invitee'ye mail
     * ve public_bookings.status güncellemesi. Idempotent — zaten canceled
     * ise tekrar çalışmaz.
     */
    private function handleCancellation(StudentAppointment $apt): void
    {
        try {
            $pb = \App\Models\PublicBooking::query()
                ->withoutGlobalScopes()
                ->where('student_appointment_id', $apt->id)
                ->first();

            if (!$pb || !$pb->isActive()) {
                return; // public booking yok veya zaten inactive
            }

            $pb->update([
                'status'      => 'canceled_by_senior',
                'canceled_at' => now(),
            ]);

            // Invitee'ye mail
            if (!empty($pb->invitee_email)) {
                $settings = \App\Models\SeniorBookingSetting::query()
                    ->withoutGlobalScopes()
                    ->where('senior_user_id', $pb->senior_user_id)
                    ->first();
                $tz          = $settings?->timezone ?: 'Europe/Berlin';
                $startsLocal = \Carbon\CarbonImmutable::parse($pb->starts_at)->setTimezone($tz)->format('d.m.Y H:i');
                $senior      = \App\Models\User::query()->withoutGlobalScopes()->where('id', $pb->senior_user_id)->first();

                $body = "Merhaba {$pb->invitee_name},\n\n"
                    . "Üzgünüz, {$startsLocal} ({$tz}) tarihli randevunuz "
                    . ($senior ? "{$senior->name} " : '')
                    . "tarafından iptal edildi.\n\n"
                    . ($apt->cancel_reason ? "Sebep: {$apt->cancel_reason}\n\n" : '')
                    . "Lütfen yeniden randevu almak için bize tekrar başvurun.";

                @\Illuminate\Support\Facades\Mail::raw(
                    $body,
                    function ($m) use ($pb) {
                        $m->to($pb->invitee_email)->subject('Randevunuz iptal edildi');
                    }
                );
            }
        } catch (\Throwable $e) {
            Log::warning('Booking cross-cancel notification failed', [
                'appointment_id' => $apt->id,
                'error'          => $e->getMessage(),
            ]);
        }
    }

    public function deleted(StudentAppointment $apt): void
    {
        if (empty($apt->google_event_id)) return;

        try {
            $this->calendar->deleteAppointment($apt);
        } catch (\Throwable $e) {
            Log::warning('Google Calendar delete observer hatası', [
                'appointment_id' => $apt->id,
                'error'          => $e->getMessage(),
            ]);
        }
    }
}
