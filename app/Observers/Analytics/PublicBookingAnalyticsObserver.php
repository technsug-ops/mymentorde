<?php

namespace App\Observers\Analytics;

use App\Models\PublicBooking;
use App\Services\Analytics\AnalyticsService;

/**
 * PublicBooking lifecycle events → PostHog.
 *
 * Yakalanan event'ler:
 *   - booking_scheduled  (created)
 *   - booking_completed  (status → completed)
 *   - booking_cancelled  (status → canceled_by_*)
 */
class PublicBookingAnalyticsObserver
{
    public function __construct(private readonly AnalyticsService $analytics) {}

    public function created(PublicBooking $booking): void
    {
        $leadTimeHours = null;
        if ($booking->starts_at) {
            $leadTimeHours = (int) now()->diffInHours($booking->starts_at, false);
        }

        $this->analytics->capture('booking_scheduled', [
            'booking_id'         => $booking->id,
            'senior_id'          => $booking->senior_user_id,
            'student_id'         => $booking->student_user_id,
            'guest_application_id' => $booking->guest_application_id,
            'booked_by_user_id'  => $booking->booked_by_user_id,
            'scheduled_at'       => optional($booking->starts_at)->toIso8601String(),
            'lead_time_hours'    => $leadTimeHours,
            'status'             => $booking->status,
            'company_id'         => $booking->company_id,
            'source'             => $booking->booked_by_user_id ? 'self_service' : 'public',
        ], $this->distinctIdFor($booking));
    }

    public function updated(PublicBooking $booking): void
    {
        if (!$booking->wasChanged('status')) {
            return;
        }

        $old = (string) $booking->getOriginal('status');
        $new = (string) $booking->status;

        // booking_completed
        if ($new === 'completed') {
            $duration = null;
            if ($booking->starts_at && $booking->ends_at) {
                $duration = (int) $booking->starts_at->diffInMinutes($booking->ends_at);
            }
            $this->analytics->capture('booking_completed', [
                'booking_id'    => $booking->id,
                'senior_id'     => $booking->senior_user_id,
                'duration_min'  => $duration,
                'from_status'   => $old,
                'notes_length'  => strlen((string) $booking->senior_notes),
                'company_id'    => $booking->company_id,
            ], $this->distinctIdFor($booking));
            return;
        }

        // booking_cancelled — herhangi bir cancel variant
        if (in_array($new, ['canceled_by_invitee', 'canceled_by_senior', 'no_show'], true)) {
            $hoursBefore = null;
            if ($booking->starts_at) {
                $hoursBefore = (int) now()->diffInHours($booking->starts_at, false);
            }
            $this->analytics->capture('booking_cancelled', [
                'booking_id'      => $booking->id,
                'senior_id'       => $booking->senior_user_id,
                'from_status'     => $old,
                'to_status'       => $new,
                'cancelled_by'    => match ($new) {
                    'canceled_by_invitee' => 'invitee',
                    'canceled_by_senior'  => 'senior',
                    'no_show'             => 'no_show',
                    default               => 'unknown',
                },
                'hours_before_scheduled' => $hoursBefore,
                'company_id'      => $booking->company_id,
            ], $this->distinctIdFor($booking));
        }
    }

    private function distinctIdFor(PublicBooking $booking): string
    {
        if (!empty($booking->student_user_id)) {
            return (string) $booking->student_user_id;
        }
        if (!empty($booking->booked_by_user_id)) {
            return (string) $booking->booked_by_user_id;
        }
        if (!empty($booking->guest_application_id)) {
            return 'lead_' . $booking->guest_application_id;
        }
        return 'booking_' . $booking->id;
    }
}
