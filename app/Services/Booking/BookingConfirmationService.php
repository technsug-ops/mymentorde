<?php

namespace App\Services\Booking;

use App\Models\GuestApplication;
use App\Models\PublicBooking;
use App\Models\SeniorBookingSetting;
use App\Models\StudentAppointment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Public booking oluştur + student_appointment'a yansıt + mail bildirimi.
 *
 * Slot availability re-check BURADA yapılır (TOCTOU engeli): kullanıcı slot'u
 * seçince cache'li slot döndürülse de confirm anında DB + live check yapılır.
 */
class BookingConfirmationService
{
    public function __construct(
        private readonly SlotGeneratorService $slotGenerator = new SlotGeneratorService()
    ) {
    }

    /**
     * @param array{
     *   senior_user_id: int,
     *   starts_at_iso: string,  // ISO 8601 in senior TZ
     *   invitee_name: string,
     *   invitee_email: string,
     *   invitee_phone?: ?string,
     *   notes?: ?string,
     *   booked_by_user_id?: ?int,
     *   student_user_id?: ?int,
     *   guest_application_id?: ?int,
     * } $input
     *
     * @throws \DomainException slot no longer available
     * @throws \InvalidArgumentException settings inactive
     */
    public function confirm(array $input): PublicBooking
    {
        $settings = SeniorBookingSetting::query()
            ->withoutGlobalScopes()
            ->where('senior_user_id', $input['senior_user_id'])
            ->first();

        if (!$settings || !$settings->is_active) {
            throw new \InvalidArgumentException('Senior randevu sistemi aktif değil.');
        }

        $tz        = $settings->timezone ?: 'Europe/Berlin';
        $startsAt  = CarbonImmutable::parse($input['starts_at_iso'])->setTimezone($tz);
        $endsAt    = $startsAt->copy()->addMinutes((int) $settings->slot_duration);

        // Re-check: slot hala boş mu?
        $this->assertSlotAvailable($settings, $startsAt, $endsAt);

        return DB::transaction(function () use ($input, $settings, $startsAt, $endsAt) {
            // 1. public_bookings row
            $booking = PublicBooking::create([
                'company_id'           => $settings->company_id,
                'senior_user_id'       => $settings->senior_user_id,
                'booked_by_user_id'    => $input['booked_by_user_id'] ?? null,
                'student_user_id'      => $input['student_user_id'] ?? null,
                'guest_application_id' => $input['guest_application_id'] ?? null,
                'invitee_name'         => $input['invitee_name'],
                'invitee_email'        => $input['invitee_email'],
                'invitee_phone'        => $input['invitee_phone'] ?? null,
                'starts_at'            => $startsAt->setTimezone('UTC'),
                'ends_at'              => $endsAt->setTimezone('UTC'),
                'status'               => 'confirmed',
                'notes'                => $input['notes'] ?? null,
            ]);

            // 2. student_appointments row (Google Calendar sync için)
            $studentId = $this->resolveStudentIdForBooking($booking);
            $senior    = User::query()->withoutGlobalScopes()->where('id', $settings->senior_user_id)->first();

            $appointment = StudentAppointment::create([
                'company_id'       => $settings->company_id,
                'student_id'       => $studentId,
                'student_email'    => $input['invitee_email'],
                'senior_email'     => $senior?->email,
                'title'            => $settings->display_name ?: 'Randevu',
                'note'             => $this->formatAppointmentNote($booking),
                'scheduled_at'     => $startsAt->setTimezone('UTC'),
                'duration_minutes' => (int) $settings->slot_duration,
                'channel'          => 'online',
                'status'           => 'scheduled',
            ]);

            // 3. Link back
            $booking->update(['student_appointment_id' => $appointment->id]);

            // 4. Mail bildirim (fail-safe, booking'i bozmasın)
            $this->sendConfirmationMails($booking, $settings, $senior);

            return $booking->fresh();
        });
    }

    /**
     * @throws \DomainException
     */
    public function cancel(PublicBooking $booking, string $reason, string $canceledBy): PublicBooking
    {
        if (!$booking->isActive()) {
            throw new \DomainException('Bu randevu zaten aktif değil.');
        }

        return DB::transaction(function () use ($booking, $reason, $canceledBy) {
            $statusKey = $canceledBy === 'senior' ? 'canceled_by_senior' : 'canceled_by_invitee';
            $booking->update([
                'status'       => $statusKey,
                'senior_notes' => $canceledBy === 'senior'
                    ? trim(($booking->senior_notes ? $booking->senior_notes . "\n" : '') . 'İptal: ' . $reason)
                    : $booking->senior_notes,
                'notes'        => $canceledBy === 'invitee'
                    ? trim(($booking->notes ? $booking->notes . "\n" : '') . 'İptal: ' . $reason)
                    : $booking->notes,
                'canceled_at'  => now(),
            ]);

            // Student appointment'ı iptal et (observer Google'dan da siler)
            if ($booking->student_appointment_id) {
                $apt = StudentAppointment::withoutGlobalScopes()->find($booking->student_appointment_id);
                if ($apt) {
                    $apt->update([
                        'status'        => 'cancelled',
                        'cancelled_at'  => now(),
                        'cancel_reason' => $reason,
                    ]);
                }
            }

            return $booking->fresh();
        });
    }

    private function assertSlotAvailable(
        SeniorBookingSetting $settings,
        CarbonImmutable $startsAt,
        CarbonImmutable $endsAt
    ): void {
        $dateSlots = $this->slotGenerator->generateForSenior(
            $settings->senior_user_id,
            $startsAt->startOfDay(),
            $startsAt->endOfDay(),
            useCache: false
        );
        $dayKey = $startsAt->toDateString();

        if (empty($dateSlots[$dayKey])) {
            throw new \DomainException('Seçilen gün için boş slot yok.');
        }

        $targetIso = $startsAt->toIso8601String();
        foreach ($dateSlots[$dayKey] as $slot) {
            if ($slot['iso_starts_at'] === $targetIso) {
                return;
            }
        }
        throw new \DomainException('Seçtiğiniz saat artık boş değil. Lütfen başka bir saat seçin.');
    }

    private function resolveStudentIdForBooking(PublicBooking $booking): string
    {
        // Login student → kendi student_id'si
        if ($booking->student_user_id) {
            $user = User::query()->withoutGlobalScopes()->where('id', $booking->student_user_id)->first();
            if ($user && !empty($user->student_id)) {
                return (string) $user->student_id;
            }
        }
        // Login guest → GUEST-{id} veya converted student_id
        if ($booking->guest_application_id) {
            $guest = GuestApplication::query()->withoutGlobalScopes()->where('id', $booking->guest_application_id)->first();
            if ($guest) {
                if (!empty($guest->converted_student_id)) {
                    return (string) $guest->converted_student_id;
                }
                return 'GUEST-' . $guest->id;
            }
        }
        // Anonim public booking → synthetic
        return 'PB-' . str_pad((string) $booking->id, 8, '0', STR_PAD_LEFT);
    }

    private function formatAppointmentNote(PublicBooking $booking): string
    {
        $parts = [
            "Public Booking: {$booking->invitee_name} <{$booking->invitee_email}>",
        ];
        if ($booking->invitee_phone) {
            $parts[] = "Tel: {$booking->invitee_phone}";
        }
        if ($booking->notes) {
            $parts[] = "Not: {$booking->notes}";
        }
        $parts[] = "Token: {$booking->booking_token}";
        return implode("\n", $parts);
    }

    private function sendConfirmationMails(PublicBooking $booking, SeniorBookingSetting $settings, ?User $senior): void
    {
        try {
            $cancelUrl = route('booking.public.cancel.show', ['token' => $booking->booking_token]);
            $tz        = $settings->timezone ?: 'Europe/Berlin';
            $startsLocal = CarbonImmutable::parse($booking->starts_at)->setTimezone($tz)->format('d.m.Y H:i');

            $subject = "Randevu onaylandı — {$settings->display_name}";
            $bodyInvitee = "Merhaba {$booking->invitee_name},\n\n"
                . "Randevunuz onaylandı.\n\n"
                . "Tarih/Saat: {$startsLocal} ({$tz})\n"
                . "Süre: {$settings->slot_duration} dakika\n"
                . ($senior ? "Danışman: {$senior->name}\n\n" : "\n")
                . "Randevuyu iptal etmek için:\n{$cancelUrl}\n";

            if (function_exists('mail') && !empty($booking->invitee_email)) {
                @\Illuminate\Support\Facades\Mail::raw($bodyInvitee, function ($m) use ($booking, $subject) {
                    $m->to($booking->invitee_email)->subject($subject);
                });
            }

            if ($senior && $senior->email) {
                $seniorBody = "Yeni randevu: {$booking->invitee_name} ({$booking->invitee_email})\n"
                    . "Tarih/Saat: {$startsLocal} ({$tz})\n"
                    . "Süre: {$settings->slot_duration} dakika\n"
                    . ($booking->notes ? "Not: {$booking->notes}\n" : '');
                @\Illuminate\Support\Facades\Mail::raw($seniorBody, function ($m) use ($senior) {
                    $m->to($senior->email)->subject('Yeni randevu alındı');
                });
            }
        } catch (\Throwable $e) {
            Log::warning('Booking mail gönderimi başarısız', ['error' => $e->getMessage()]);
        }
    }
}
