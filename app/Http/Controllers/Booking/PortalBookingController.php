<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Models\GuestApplication;
use App\Models\SeniorBookingSetting;
use App\Models\User;
use App\Models\PublicBooking;
use App\Services\Booking\BookingConfirmationService;
use App\Services\Booking\PricingResolver;
use App\Services\Booking\SlotGeneratorService;
use App\Services\Booking\StripeCheckoutService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Portal Booking — Guest/Student için paylaşımlı senior havuzu seçim widget'ı.
 *
 * Phase 4 (Marketplace):
 *   - directory() → kullanıcı login altında tüm aktif senior'ları görür
 *   - show($slug) → seçilen senior için slot picker (mevcut public widget'ı login içine taşır)
 *   - confirm() → user contracted ise ücretsiz, değilse Phase 5 ödeme akışına yönlendirir
 *
 * Eski public flow (PublicBookingController) dış dünyaya açıktır;
 * bu controller yalnızca authenticated guest/student için kullanılır.
 */
class PortalBookingController extends Controller
{
    public function __construct(
        private readonly SlotGeneratorService $slotGenerator,
        private readonly BookingConfirmationService $confirmation,
        private readonly StripeCheckoutService $stripe = new StripeCheckoutService(),
        private readonly PricingResolver $pricing = new PricingResolver(),
    ) {
    }

    /**
     * Senior directory — guest/student portal sidebar'ından gelen kullanıcıya
     * paylaşımlı havuzdan senior listesi gösterir.
     *
     * Sözleşmeli (student / signed-guest) → "Ücretsiz" rozetiyle görünür.
     * Diğerleri için → Phase 5'te aktive olacak fiyat bilgisi gösterilir.
     */
    public function directory(Request $request): View
    {
        $this->assertPortalUser($request);

        $topic = trim((string) $request->query('topic', ''));
        $lang  = trim((string) $request->query('lang', ''));

        // Aktif tüm senior_booking_settings → portal kullanıcısı public_slug bazlı
        // erişebilir, public olmasına bile gerek yok (login zaten yapıldı).
        $settings = SeniorBookingSetting::query()
            ->withoutGlobalScopes()
            ->where('is_active', true)
            ->whereNotNull('public_slug')
            ->orderBy('display_name')
            ->get();

        $seniorIds = $settings->pluck('senior_user_id')->all();
        $seniors = User::query()
            ->withoutGlobalScopes()
            ->whereIn('id', $seniorIds)
            ->get(['id', 'name', 'email', 'bio', 'photo_url', 'expertise_tags', 'senior_type'])
            ->keyBy('id');

        $isContracted = $this->resolveIsContracted($request);

        $cards = $settings->map(function (SeniorBookingSetting $s) use ($seniors, $isContracted) {
            $u = $seniors->get($s->senior_user_id);
            $expertise = $u ? $u->expertiseTags() : [];

            return [
                'slug'          => $s->public_slug,
                'display_name'  => $s->display_name ?: ($u?->name ?? 'Danışman'),
                'name'          => $u?->name,
                'bio'           => $this->truncate($u?->bio, 220),
                'photo_url'     => $u?->photo_url,
                'expertise'     => $expertise,
                'languages'     => $this->resolveLanguages($expertise),
                'topics'        => $this->resolveTopics($expertise),
                'slot_duration' => (int) $s->slot_duration,
                'timezone'      => $s->timezone,
                'is_free'       => $isContracted,
                'price_label'   => $isContracted
                    ? 'Sözleşmen kapsamında ücretsiz'
                    : 'Yakında ücretli randevu',
            ];
        });

        // Filtreler — topic & language case-insensitive substring match
        $filtered = $cards->filter(function (array $c) use ($topic, $lang) {
            if ($topic !== '') {
                $hay = strtolower(implode(' ', array_merge($c['topics'], $c['expertise'])));
                if (!str_contains($hay, strtolower($topic))) return false;
            }
            if ($lang !== '') {
                $hay = strtolower(implode(' ', $c['languages']));
                if (!str_contains($hay, strtolower($lang))) return false;
            }
            return true;
        })->values();

        return view('portal.booking-directory', [
            'cards'        => $filtered,
            'totalCount'   => $cards->count(),
            'filterTopic'  => $topic,
            'filterLang'   => $lang,
            'isContracted' => $isContracted,
            'portalRole'   => $this->portalRole($request),
            'routeName'    => $this->routeBaseForUser($request),
        ]);
    }

    /**
     * Tek senior detay sayfası — slot picker + onay formu.
     */
    public function show(Request $request, string $slug): View
    {
        $this->assertPortalUser($request);

        $settings = $this->resolveSettingsBySlug($slug);
        $senior   = User::query()->withoutGlobalScopes()->where('id', $settings->senior_user_id)->first();

        $tz       = $settings->timezone ?: 'Europe/Berlin';
        $fromDate = CarbonImmutable::today($tz)->toDateString();
        $toDate   = CarbonImmutable::today($tz)->addDays((int) $settings->max_future_days)->toDateString();

        $user = $request->user();
        $isContracted = $this->resolveIsContracted($request);

        return view('portal.booking-show', [
            'settings'     => $settings,
            'senior'       => $senior,
            'fromDate'     => $fromDate,
            'toDate'       => $toDate,
            'isContracted' => $isContracted,
            'portalRole'   => $this->portalRole($request),
            'routeName'    => $this->routeBaseForUser($request),
            'prefill'      => [
                'invitee_name'  => (string) ($user->name ?? ''),
                'invitee_email' => (string) ($user->email ?? ''),
                'invitee_phone' => (string) ($user->phone ?? ''),
            ],
        ]);
    }

    /**
     * AJAX slot listesi — frontend gün penceresinde boş saatleri çeker.
     */
    public function slots(Request $request, string $slug): JsonResponse
    {
        $this->assertPortalUser($request);
        $settings = $this->resolveSettingsBySlug($slug);

        $data = $request->validate([
            'from' => 'required|date',
            'to'   => 'required|date|after_or_equal:from',
        ]);

        $tz   = $settings->timezone ?: 'Europe/Berlin';
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

    /**
     * Booking onayı — sözleşmeli ise direkt confirmFree, değilse Phase 5'e bekleme.
     */
    public function confirm(Request $request, string $slug): JsonResponse
    {
        $this->assertPortalUser($request);
        $settings = $this->resolveSettingsBySlug($slug);

        $data = $request->validate([
            'starts_at_iso' => 'required|string',
            'invitee_name'  => 'required|string|max:180',
            'invitee_email' => 'required|email|max:180',
            'invitee_phone' => 'nullable|string|max:64',
            'notes'         => 'nullable|string|max:2000',
        ]);

        $user = $request->user();
        $isContracted = $this->resolveIsContracted($request);

        $payload = [
            'senior_user_id' => $settings->senior_user_id,
            'starts_at_iso'  => $data['starts_at_iso'],
            'invitee_name'   => trim($data['invitee_name']),
            'invitee_email'  => strtolower(trim($data['invitee_email'])),
            'invitee_phone'  => $data['invitee_phone'] ?? null,
            'notes'          => $data['notes'] ?? null,
            'booked_by_user_id' => $user->id,
        ];

        $role = (string) ($user->role ?? '');
        if ($role === User::ROLE_STUDENT) {
            $payload['student_user_id'] = $user->id;
        } elseif ($role === User::ROLE_GUEST) {
            $guest = GuestApplication::query()
                ->withoutGlobalScopes()
                ->where('guest_user_id', $user->id)
                ->orderByDesc('id')
                ->first();
            if ($guest) {
                $payload['guest_application_id'] = $guest->id;
            }
        }

        // Sözleşmeli user → mevcut ücretsiz akış (Phase 5'te değişmedi)
        if ($isContracted) {
            try {
                $booking = $this->confirmation->confirm($payload);
            } catch (\DomainException $e) {
                return response()->json(['ok' => false, 'error' => $e->getMessage()], 409);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Portal booking confirm failed (free)', [
                    'slug'  => $slug,
                    'user'  => $user->id ?? null,
                    'error' => $e->getMessage(),
                ]);
                return response()->json(['ok' => false, 'error' => 'Beklenmeyen bir hata oluştu.'], 500);
            }

            return response()->json([
                'ok'            => true,
                'booking_token' => $booking->booking_token,
                'redirect_url'  => $this->successRedirectFor($request),
                'cancel_url'    => route('booking.public.cancel.show', ['token' => $booking->booking_token]),
            ]);
        }

        // ── Phase 5 — Ücretli akış: pending_payment booking + Stripe Checkout ──
        try {
            $booking = $this->createPendingPaymentBooking($settings, $payload);
        } catch (\DomainException $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 409);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Portal booking pending_payment create failed', [
                'slug'  => $slug,
                'user'  => $user->id ?? null,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['ok' => false, 'error' => 'Randevu kaydedilemedi.'], 500);
        }

        // Stripe Checkout başlat
        try {
            $session = $this->stripe->createCheckoutSession(
                $booking,
                sprintf('Randevu: %s — %d dk', $settings->display_name ?: 'MentorDE', (int) $settings->slot_duration)
            );
        } catch (\DomainException $e) {
            // Booking row var ama Stripe başlatılamadı — kayıt expired/failed olarak işaretle
            $booking->update([
                'payment_status'         => 'failed',
                'status'                 => 'canceled_by_invitee',
                'canceled_at'            => now(),
                'payment_failure_reason' => mb_substr($e->getMessage(), 0, 250),
            ]);
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 402);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Stripe checkout session create failed', [
                'booking_id' => $booking->id,
                'error'      => $e->getMessage(),
            ]);
            return response()->json(['ok' => false, 'error' => 'Ödeme sayfası açılamadı. Lütfen tekrar deneyin.'], 502);
        }

        return response()->json([
            'ok'            => true,
            'booking_token' => $booking->booking_token,
            'redirect_url'  => $session['url'],
            'stripe'        => true,
            'expires_at'    => $session['expires_at'],
        ]);
    }

    /**
     * Phase 5 — pending_payment booking row'u oluştur ve Stripe checkout için hazırla.
     * Slot availability kontrolü + pricing resolution + DB write (transaction).
     *
     * @throws \DomainException slot artik bos degil veya fiyat tanimli degil
     */
    private function createPendingPaymentBooking(SeniorBookingSetting $settings, array $payload): PublicBooking
    {
        $tz       = $settings->timezone ?: 'Europe/Berlin';
        $startsAt = CarbonImmutable::parse($payload['starts_at_iso'])->setTimezone($tz);
        $endsAt   = $startsAt->copy()->addMinutes((int) $settings->slot_duration);

        // Slot re-check
        $dayKey   = $startsAt->toDateString();
        $daySlots = $this->slotGenerator->generateForSenior(
            $settings->senior_user_id,
            $startsAt->startOfDay(),
            $startsAt->endOfDay(),
            useCache: false
        );
        if (empty($daySlots[$dayKey])) {
            throw new \DomainException('Seçilen gün için boş slot yok.');
        }
        $targetIso = $startsAt->toIso8601String();
        $found = false;
        foreach ($daySlots[$dayKey] as $slot) {
            if ($slot['iso_starts_at'] === $targetIso) { $found = true; break; }
        }
        if (!$found) {
            throw new \DomainException('Seçtiğiniz saat artık boş değil. Lütfen başka bir saat seçin.');
        }

        // Pricing
        $resolved = $this->pricing->resolve(
            companyId: (int) $settings->company_id,
            durationMinutes: (int) $settings->slot_duration,
            customerCountryCode: $payload['customer_country_code'] ?? null,
            customerType: $payload['customer_type'] ?? 'b2c',
            seniorUserId: (int) $settings->senior_user_id,
            serviceType: $payload['service_type'] ?? null,
            isContractedUser: false,
        );

        if ($resolved['is_free'] || $resolved['amount_gross_cents'] <= 0) {
            throw new \DomainException('Bu randevu için fiyat tanımlı değil veya ücretli akış kapalı.');
        }

        return \Illuminate\Support\Facades\DB::transaction(function () use ($settings, $payload, $startsAt, $endsAt, $resolved) {
            return PublicBooking::create([
                'company_id'            => $settings->company_id,
                'senior_user_id'        => $settings->senior_user_id,
                'booked_by_user_id'     => $payload['booked_by_user_id'] ?? null,
                'student_user_id'       => $payload['student_user_id'] ?? null,
                'guest_application_id'  => $payload['guest_application_id'] ?? null,
                'invitee_name'          => $payload['invitee_name'],
                'invitee_email'         => $payload['invitee_email'],
                'invitee_phone'         => $payload['invitee_phone'] ?? null,
                'customer_country_code' => $payload['customer_country_code'] ?? null,
                'customer_type'         => $payload['customer_type'] ?? 'b2c',
                'is_contracted_user'    => false,
                'starts_at'             => $startsAt->setTimezone('UTC'),
                'ends_at'               => $endsAt->setTimezone('UTC'),
                'status'                => 'pending_confirm',
                'notes'                 => $payload['notes'] ?? null,
                'amount_net_cents'      => $resolved['amount_net_cents'],
                'tax_rate_pct_applied'  => $resolved['tax_rate_pct'],
                'tax_amount_cents'      => $resolved['tax_amount_cents'],
                'amount_gross_cents'    => $resolved['amount_gross_cents'],
                'currency'              => $resolved['currency'],
                'payment_status'        => 'pending_payment',
            ]);
        });
    }

    // ──────────────────────────────────────────────────────────────────
    // helpers
    // ──────────────────────────────────────────────────────────────────

    private function assertPortalUser(Request $request): void
    {
        $user = $request->user();
        if (!$user) {
            abort(403, 'Bu sayfaya erişim için giriş yapmalısınız.');
        }
        $role = (string) ($user->role ?? '');
        if (!in_array($role, [User::ROLE_STUDENT, User::ROLE_GUEST], true)) {
            abort(403, 'Bu sayfa sadece öğrenci ve aday öğrenciler içindir.');
        }
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

    /**
     * BookingConfirmationService::isContractedUser ile aynı sözleşme tanımı —
     * student → her zaman true, guest → contract_status signed/approved ise true.
     */
    private function resolveIsContracted(Request $request): bool
    {
        $user = $request->user();
        if (!$user) return false;

        $role = (string) ($user->role ?? '');

        if ($role === User::ROLE_STUDENT) {
            return $this->confirmation->isContractedUser($user->id, null);
        }
        if ($role === User::ROLE_GUEST) {
            $guest = GuestApplication::query()
                ->withoutGlobalScopes()
                ->where('guest_user_id', $user->id)
                ->orderByDesc('id')
                ->first(['id']);
            return $this->confirmation->isContractedUser(null, $guest?->id);
        }
        return false;
    }

    private function portalRole(Request $request): string
    {
        return (string) ($request->user()?->role ?? 'guest');
    }

    /**
     * Şu portal için route ismi prefix'i — view tarafından link/POST hedefi seçimi.
     * (guest.booking.directory.*, student.booking.directory.*)
     */
    private function routeBaseForUser(Request $request): string
    {
        return $this->portalRole($request) === User::ROLE_STUDENT
            ? 'student.booking.directory'
            : 'guest.booking.directory';
    }

    private function successRedirectFor(Request $request): string
    {
        if ($this->portalRole($request) === User::ROLE_STUDENT) {
            return route('student.appointments');
        }
        return route('guest.booking');
    }

    /**
     * Expertise tag'lerinden dil bilgisi türet ('tr','en','de','almanca','ingilizce' vb.).
     *
     * @param  array<int,string>  $tags
     * @return array<int,string>
     */
    private function resolveLanguages(array $tags): array
    {
        $map = [
            'tr' => 'Türkçe', 'turkce' => 'Türkçe', 'türkçe' => 'Türkçe',
            'en' => 'İngilizce', 'ingilizce' => 'İngilizce', 'english' => 'İngilizce',
            'de' => 'Almanca', 'almanca' => 'Almanca', 'deutsch' => 'Almanca', 'german' => 'Almanca',
        ];
        $out = [];
        foreach ($tags as $t) {
            $k = strtolower(trim((string) $t));
            if (isset($map[$k]) && !in_array($map[$k], $out, true)) {
                $out[] = $map[$k];
            }
        }
        if (empty($out)) {
            $out = ['Türkçe'];
        }
        return $out;
    }

    /**
     * Expertise tag'lerinden konu başlıkları (dil/lisans-master tag'lerini çıkar).
     *
     * @param  array<int,string>  $tags
     * @return array<int,string>
     */
    private function resolveTopics(array $tags): array
    {
        $skip = ['tr','en','de','türkçe','turkce','ingilizce','english','almanca','deutsch','german',
                 'bachelor','lisans','master','yuksek_lisans','yüksek_lisans'];
        $out = [];
        foreach ($tags as $t) {
            $raw = trim((string) $t);
            if ($raw === '') continue;
            $k = strtolower($raw);
            if (in_array($k, $skip, true)) continue;
            $out[] = ucfirst($raw);
        }
        return array_values(array_unique($out));
    }

    private function truncate(?string $s, int $len): string
    {
        $s = trim((string) ($s ?? ''));
        if ($s === '') return '';
        if (mb_strlen($s) <= $len) return $s;
        return mb_substr($s, 0, $len - 1) . '…';
    }
}
