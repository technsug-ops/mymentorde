<?php

namespace App\Jobs;

use App\Models\PublicBooking;
use App\Models\SeniorBookingSetting;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Marketplace Phase 7 — Booking tamamlandiktan sonra invitee'ye review davetiyesi.
 *
 *   - QueueReviewRequestsCommand (hourly schedule) BookingId'leri tarayip dispatch eder
 *   - Job, invitee_email'e signed-link iceren plain-text mail gonderir
 *   - public_bookings.review_request_sent_at guncellenir (tek seferlik gonderim icin)
 *
 * Fail-safe: SeniorReview tablosu yoksa / mail driver kapaliysa job sessizce no-op.
 */
class SendReviewRequestEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 120;

    public function __construct(public int $publicBookingId)
    {
    }

    public function handle(): void
    {
        $booking = PublicBooking::query()
            ->withoutGlobalScopes()
            ->where('id', $this->publicBookingId)
            ->first();

        if (!$booking) {
            return;
        }

        // Sadece tamamlanmis bookingler icin
        if (!in_array($booking->status, ['confirmed', 'completed'], true)) {
            return;
        }

        // Idempotent — tek seferlik gonderim
        if (!empty($booking->review_request_sent_at)) {
            return;
        }

        if (empty($booking->invitee_email)) {
            return;
        }

        $senior  = User::query()->withoutGlobalScopes()->where('id', $booking->senior_user_id)->first();
        $setting = SeniorBookingSetting::query()
            ->withoutGlobalScopes()
            ->where('senior_user_id', $booking->senior_user_id)
            ->first();

        $seniorName = $setting?->display_name ?: ($senior?->name ?? 'Mentörde Uzmanı');
        $reviewUrl  = route('booking.review.show', ['token' => $booking->booking_token]);
        $whenStr    = optional($booking->starts_at)->format('d.m.Y');

        $body =
            "Merhaba {$booking->invitee_name},\n\n"
            . "{$seniorName} ile {$whenStr} tarihindeki görüşmen nasıl geçti?\n\n"
            . "Birkaç dakikanı ayırıp deneyimini paylaşır mısın? Geri bildirimin hem uzmana hem de gelecekteki adaylara yardımcı oluyor.\n\n"
            . "Değerlendirme bağlantın:\n{$reviewUrl}\n\n"
            . "Bağlantı 30 gün geçerli. Bu bir kerelik bir hatırlatmadır.\n\n"
            . "Teşekkürler,\n"
            . "— MentorDE Ekibi";

        try {
            Mail::raw($body, function ($m) use ($booking, $seniorName) {
                $m->to($booking->invitee_email)
                  ->subject("{$seniorName} ile görüşmeni değerlendir");
            });

            $booking->update(['review_request_sent_at' => now()]);
        } catch (\Throwable $e) {
            Log::warning('review_request.mail_failed', [
                'booking_id' => $booking->id,
                'error'      => $e->getMessage(),
            ]);
        }
    }
}
