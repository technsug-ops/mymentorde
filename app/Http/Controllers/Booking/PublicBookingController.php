<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Models\PublicBooking;
use App\Models\SeniorBookingSetting;
use App\Models\User;
use App\Services\Booking\BookingConfirmationService;
use App\Services\Booking\SlotGeneratorService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Public booking widget — /book/{slug}
 *
 * Erişim kuralları:
 *   - Login olmuş student/guest → slug doğruysa her zaman erişir
 *   - Login yoksa → sadece is_public=true ise erişir
 */
class PublicBookingController extends Controller
{
    public function __construct(
        private readonly SlotGeneratorService $slotGenerator,
        private readonly BookingConfirmationService $confirmation
    ) {
    }

    public function show(Request $request, string $slug): View
    {
        $settings = $this->resolveSettingsBySlug($slug);
        $this->assertAccessible($request, $settings);

        $senior = User::query()->withoutGlobalScopes()->where('id', $settings->senior_user_id)->first();

        return view('booking.public.widget', [
            'settings'  => $settings,
            'senior'    => $senior,
            'fromDate'  => CarbonImmutable::today($settings->timezone)->toDateString(),
            'toDate'    => CarbonImmutable::today($settings->timezone)->addDays((int) $settings->max_future_days)->toDateString(),
            'prefill'   => $this->prefillFromAuth($request),
        ]);
    }

    /** AJAX: slot listesi (7 gün penceresi). */
    public function slots(Request $request, string $slug): JsonResponse
    {
        $settings = $this->resolveSettingsBySlug($slug);
        $this->assertAccessible($request, $settings);

        $data = $request->validate([
            'from' => 'required|date',
            'to'   => 'required|date|after_or_equal:from',
        ]);

        $tz = $settings->timezone ?: 'Europe/Berlin';
        $from = CarbonImmutable::parse($data['from'], $tz)->startOfDay();
        $to   = CarbonImmutable::parse($data['to'], $tz)->endOfDay();

        $days = $this->slotGenerator->generateForSenior(
            $settings->senior_user_id,
            $from,
            $to
        );

        return response()->json([
            'ok'       => true,
            'timezone' => $tz,
            'days'     => $days,
        ]);
    }

    public function confirm(Request $request, string $slug): JsonResponse
    {
        $settings = $this->resolveSettingsBySlug($slug);
        $this->assertAccessible($request, $settings);

        $data = $request->validate([
            'starts_at_iso' => 'required|string',
            'invitee_name'  => 'required|string|max:180',
            'invitee_email' => 'required|email|max:180',
            'invitee_phone' => 'nullable|string|max:64',
            'notes'         => 'nullable|string|max:2000',
        ]);

        $user = $request->user();
        $payload = [
            'senior_user_id' => $settings->senior_user_id,
            'starts_at_iso'  => $data['starts_at_iso'],
            'invitee_name'   => trim($data['invitee_name']),
            'invitee_email'  => strtolower(trim($data['invitee_email'])),
            'invitee_phone'  => $data['invitee_phone'] ?? null,
            'notes'          => $data['notes'] ?? null,
        ];

        if ($user) {
            $payload['booked_by_user_id'] = $user->id;
            if (($user->role ?? '') === User::ROLE_STUDENT) {
                $payload['student_user_id'] = $user->id;
            }
            if (($user->role ?? '') === User::ROLE_GUEST) {
                $guest = \App\Models\GuestApplication::query()
                    ->withoutGlobalScopes()
                    ->where('guest_user_id', $user->id)
                    ->orderByDesc('id')
                    ->first();
                if ($guest) {
                    $payload['guest_application_id'] = $guest->id;
                }
            }
        }

        try {
            $booking = $this->confirmation->confirm($payload);
        } catch (\DomainException $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 409);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Public booking confirm failed', [
                'slug'  => $slug,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['ok' => false, 'error' => 'Beklenmeyen bir hata oluştu.'], 500);
        }

        return response()->json([
            'ok'            => true,
            'booking_token' => $booking->booking_token,
            'cancel_url'    => route('booking.public.cancel.show', ['token' => $booking->booking_token]),
        ]);
    }

    public function cancelShow(string $token): View
    {
        $booking = $this->resolveBookingByToken($token);
        $settings = SeniorBookingSetting::query()
            ->withoutGlobalScopes()
            ->where('senior_user_id', $booking->senior_user_id)
            ->first();
        $senior = User::query()->withoutGlobalScopes()->where('id', $booking->senior_user_id)->first();

        return view('booking.public.cancel', [
            'booking'  => $booking,
            'settings' => $settings,
            'senior'   => $senior,
        ]);
    }

    public function cancel(Request $request, string $token): RedirectResponse
    {
        $booking = $this->resolveBookingByToken($token);

        $data = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);
        $reason = trim($data['reason'] ?? '');
        if ($reason === '') {
            $reason = '(gerekçe belirtilmedi)';
        }

        try {
            $this->confirmation->cancel($booking, $reason, 'invitee');
        } catch (\DomainException $e) {
            return back()->withErrors(['cancel' => $e->getMessage()]);
        }

        return redirect()
            ->route('booking.public.cancel.show', ['token' => $token])
            ->with('status', 'Randevunuz iptal edildi.');
    }

    private function resolveSettingsBySlug(string $slug): SeniorBookingSetting
    {
        $settings = SeniorBookingSetting::query()
            ->withoutGlobalScopes()
            ->where('public_slug', $slug)
            ->first();
        if (!$settings || !$settings->is_active) {
            abort(404, 'Randevu sayfası bulunamadı.');
        }
        return $settings;
    }

    private function resolveBookingByToken(string $token): PublicBooking
    {
        $booking = PublicBooking::query()
            ->withoutGlobalScopes()
            ->where('booking_token', $token)
            ->first();
        if (!$booking) {
            abort(404, 'Randevu bulunamadı.');
        }
        return $booking;
    }

    private function assertAccessible(Request $request, SeniorBookingSetting $settings): void
    {
        if ($settings->is_public) {
            return;
        }
        $user = $request->user();
        if (!$user) {
            abort(403, 'Bu randevu sayfasına erişim için giriş yapmalısınız.');
        }
        $role = $user->role ?? '';
        if (!in_array($role, [User::ROLE_STUDENT, User::ROLE_GUEST], true)) {
            // Opsiyonel: diğer roller de login ile erişebilir, ama Phase 1 kuralı guest/student
            abort(403, 'Bu randevu sayfası public olarak açık değil.');
        }
    }

    /** @return array{invitee_name:string,invitee_email:string,invitee_phone:?string} */
    private function prefillFromAuth(Request $request): array
    {
        $user = $request->user();
        if (!$user) {
            return ['invitee_name' => '', 'invitee_email' => '', 'invitee_phone' => null];
        }
        return [
            'invitee_name'  => (string) ($user->name ?? ''),
            'invitee_email' => (string) ($user->email ?? ''),
            'invitee_phone' => (string) ($user->phone ?? ''),
        ];
    }
}
