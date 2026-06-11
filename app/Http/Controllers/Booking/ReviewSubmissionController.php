<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Models\PublicBooking;
use App\Models\SeniorBookingSetting;
use App\Models\SeniorReview;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Marketplace Phase 7 — Public review submission akisi.
 *
 *   GET  /review/{token}    -> form goster (signed URL gerekli, 30 gun)
 *   POST /review/{token}    -> rating + title + body kaydet (idempotent: re-submit
 *                              olursa eski review guncellenir)
 *
 * Booking token PublicBooking.booking_token ile eslestirilir; her booking icin
 * yalnizca 1 review (DB unique). Booking sahibi yorum yaziyorsa is_verified=true.
 *
 * Spam koruma: throttle middleware + signed URL + sira disi rating reddi.
 */
class ReviewSubmissionController extends Controller
{
    public function showForm(string $token): View|RedirectResponse
    {
        $booking = PublicBooking::query()
            ->withoutGlobalScopes()
            ->where('booking_token', $token)
            ->first();

        if (!$booking) {
            abort(404, 'Bu link geçerli değil ya da süresi dolmuş.');
        }

        // Sadece tamamlanmis / confirmed bookingler icin yorum
        if (!in_array($booking->status, ['confirmed', 'completed'], true)) {
            return $this->renderState('not_eligible', $booking, null, null);
        }

        $existing = SeniorReview::query()
            ->withoutGlobalScopes()
            ->where('public_booking_id', $booking->id)
            ->first();

        $senior  = User::query()->withoutGlobalScopes()->where('id', $booking->senior_user_id)->first();
        $setting = SeniorBookingSetting::query()
            ->withoutGlobalScopes()
            ->where('senior_user_id', $booking->senior_user_id)
            ->first();

        return view('public.review-form', [
            'booking'      => $booking,
            'senior'       => $senior,
            'setting'      => $setting,
            'existing'     => $existing,
            'token'        => $token,
            'submitUrl'    => route('booking.review.submit', ['token' => $token]),
            'profileUrl'   => $setting && $setting->public_slug
                ? route('booking.public.profile', ['slug' => $setting->public_slug])
                : null,
        ]);
    }

    public function submit(Request $request, string $token): RedirectResponse|View
    {
        $data = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'title'  => 'nullable|string|max:150',
            'body'   => 'nullable|string|max:2000',
        ]);

        $booking = PublicBooking::query()
            ->withoutGlobalScopes()
            ->where('booking_token', $token)
            ->first();

        if (!$booking) {
            abort(404, 'Bu link geçerli değil.');
        }

        if (!in_array($booking->status, ['confirmed', 'completed'], true)) {
            return $this->renderState('not_eligible', $booking, null, null);
        }

        try {
            $review = SeniorReview::query()
                ->withoutGlobalScopes()
                ->where('public_booking_id', $booking->id)
                ->first();

            $payload = [
                'company_id'        => $booking->company_id,
                'public_booking_id' => $booking->id,
                'senior_user_id'    => $booking->senior_user_id,
                'reviewer_email'    => (string) $booking->invitee_email,
                'reviewer_name'     => (string) $booking->invitee_name,
                'rating'            => (int) $data['rating'],
                'title'             => $data['title'] ?? null,
                'body'              => $data['body'] ?? null,
                'is_public'         => true,
                'is_verified'       => true,
                // Auto-approve — manager moderasyonu istenirse pending'e cevrilebilir
                'moderation_status' => 'approved',
                'submitted_at'      => now(),
                'moderated_at'      => now(),
            ];

            if ($review) {
                $review->update($payload);
            } else {
                $review = SeniorReview::create($payload);
            }

            $setting = SeniorBookingSetting::query()
                ->withoutGlobalScopes()
                ->where('senior_user_id', $booking->senior_user_id)
                ->first();

            return $this->renderState('success', $booking, $review, $setting);
        } catch (\Throwable $e) {
            Log::error('senior_review.submit_failed', [
                'token' => $token,
                'error' => $e->getMessage(),
            ]);
            return back()->withErrors(['rating' => 'Değerlendirme kaydedilemedi, lütfen tekrar deneyin.'])
                ->withInput();
        }
    }

    private function renderState(string $state, PublicBooking $booking, ?SeniorReview $review, ?SeniorBookingSetting $setting): View
    {
        $senior  = User::query()->withoutGlobalScopes()->where('id', $booking->senior_user_id)->first();
        $setting ??= SeniorBookingSetting::query()
            ->withoutGlobalScopes()
            ->where('senior_user_id', $booking->senior_user_id)
            ->first();

        return view('public.review-form', [
            'state'        => $state,
            'booking'      => $booking,
            'senior'       => $senior,
            'setting'      => $setting,
            'existing'     => $review,
            'token'        => $booking->booking_token,
            'submitUrl'    => route('booking.review.submit', ['token' => $booking->booking_token]),
            'profileUrl'   => $setting && $setting->public_slug
                ? route('booking.public.profile', ['slug' => $setting->public_slug])
                : null,
        ]);
    }
}
