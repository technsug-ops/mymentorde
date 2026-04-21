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
