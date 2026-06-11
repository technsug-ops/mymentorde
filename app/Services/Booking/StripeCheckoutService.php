<?php

namespace App\Services\Booking;

use App\Models\PublicBooking;
use App\Models\SeniorBookingSetting;
use App\Models\SeniorEarning;
use App\Models\StudentAppointment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session as StripeCheckoutSession;
use Stripe\Refund as StripeRefund;
use Stripe\Stripe;
use Stripe\Webhook as StripeWebhook;

/**
 * Marketplace Phase 5 — Stripe Checkout entegrasyonu
 *
 * 1. createCheckoutSession()  — pending_payment booking için Stripe Checkout URL üretir.
 * 2. handleWebhookEvent()     — Stripe webhook event'ini parse eder, booking + earning state geçişlerini uygular.
 * 3. createRefund()           — cancel akışında 24 saat önce iptal edildiyse iade yapar.
 *
 * Tüm fiyat alanları cent (integer) cinsindendir — float yuvarlama bug'ı yok.
 * Hata durumlarında DomainException atar (controller 402/409 ile yakalar).
 *
 * Test mode toggle: config('services.stripe.mode') = 'test' veya 'live'.
 * 'test' mode'da Stripe sandbox key'i kullanılır (sk_test_*).
 */
class StripeCheckoutService
{
    /** 15 dakika — Stripe Checkout session expire süresi (booking hold). */
    public const SESSION_HOLD_MINUTES = 15;

    public function __construct(
        private readonly BookingConfirmationService $confirmation = new BookingConfirmationService()
    ) {
    }

    /**
     * Booking için Stripe Checkout session aç ve URL döndür.
     *
     * @return array{session_id:string,url:string,expires_at:string}
     * @throws \DomainException
     */
    public function createCheckoutSession(PublicBooking $booking, string $description = ''): array
    {
        $this->bootStripe();

        if (!in_array($booking->payment_status, ['pending_payment'], true)) {
            throw new \DomainException('Bu randevu için ödeme başlatılamaz (durum: ' . $booking->payment_status . ').');
        }

        if ((int) $booking->amount_gross_cents <= 0) {
            throw new \DomainException('Geçersiz ödeme tutarı.');
        }

        // Açık bir oturum varsa onu kullan (idempotent — kullanıcı geri butona basıp tekrar gelirse)
        if (!empty($booking->stripe_session_id)) {
            try {
                $existing = StripeCheckoutSession::retrieve($booking->stripe_session_id);
                if ($existing && $existing->status === 'open' && !empty($existing->url)) {
                    return [
                        'session_id' => $existing->id,
                        'url'        => $existing->url,
                        'expires_at' => optional($booking->stripe_expires_at)->toIso8601String() ?? '',
                    ];
                }
            } catch (\Throwable $e) {
                Log::info('Stripe existing session retrieve failed, creating new', [
                    'booking_id' => $booking->id,
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        $expiresAt = CarbonImmutable::now()->addMinutes(self::SESSION_HOLD_MINUTES);

        $currency = strtolower($booking->currency ?: 'eur');
        $name     = $description !== ''
            ? $description
            : sprintf('MentorDE Randevu — %s', $booking->invitee_name ?: 'Görüşme');

        $successUrl = route('booking.public.success', ['token' => $booking->booking_token]);
        $cancelUrl  = route('booking.public.cancel.show', ['token' => $booking->booking_token]);

        $session = StripeCheckoutSession::create([
            'mode'                 => 'payment',
            'payment_method_types' => ['card'],
            'line_items'           => [[
                'price_data' => [
                    'currency'     => $currency,
                    'unit_amount'  => (int) $booking->amount_gross_cents,
                    'product_data' => [
                        'name'        => $name,
                        'description' => $this->buildSessionDescription($booking),
                    ],
                ],
                'quantity' => 1,
            ]],
            'metadata' => [
                'source'        => 'mentorde_booking',
                'booking_id'    => (string) $booking->id,
                'booking_token' => $booking->booking_token,
                'company_id'    => (string) $booking->company_id,
                'senior_id'     => (string) $booking->senior_user_id,
            ],
            'customer_email' => $booking->invitee_email ?: null,
            'expires_at'     => $expiresAt->getTimestamp(),
            'success_url'    => $successUrl,
            'cancel_url'     => $cancelUrl,
        ]);

        // Booking'e session bilgisi yaz
        $booking->update([
            'stripe_session_id' => $session->id,
            'stripe_expires_at' => $expiresAt,
        ]);

        return [
            'session_id' => $session->id,
            'url'        => (string) $session->url,
            'expires_at' => $expiresAt->toIso8601String(),
        ];
    }

    /**
     * Booking ile ilgili Stripe webhook event'ini yakala ve state geçişini uygula.
     *
     * Caller (StripeWebhookController veya PaymentCheckoutController):
     *   1. constructEvent zaten yapılmış olabilir (verify edilmiş Stripe event)
     *   2. event metadata.source === 'mentorde_booking' filtreleyerek bunu çağırır
     *
     * @param  \Stripe\Event  $event
     * @return array{status:string,booking_id:?int,message:?string}
     */
    public function handleWebhookEvent(\Stripe\Event $event): array
    {
        $type = (string) $event->type;
        $object = $event->data->object ?? null;

        $bookingId = $this->extractBookingId($object);
        if (!$bookingId) {
            return ['status' => 'ignored', 'booking_id' => null, 'message' => 'no booking metadata'];
        }

        $booking = PublicBooking::query()
            ->withoutGlobalScopes()
            ->where('id', $bookingId)
            ->first();

        if (!$booking) {
            Log::warning('Stripe webhook booking not found', ['booking_id' => $bookingId, 'event' => $type]);
            return ['status' => 'not_found', 'booking_id' => $bookingId, 'message' => 'booking missing'];
        }

        return match ($type) {
            'checkout.session.completed' => $this->onSessionCompleted($booking, $object),
            'checkout.session.expired'   => $this->onSessionExpired($booking, $object),
            'charge.refunded',
            'payment_intent.refunded'    => $this->onChargeRefunded($booking, $object),
            'payment_intent.payment_failed',
            'checkout.session.async_payment_failed' => $this->onPaymentFailed($booking, $object),
            default => ['status' => 'ignored', 'booking_id' => $bookingId, 'message' => "unhandled type {$type}"],
        };
    }

    /**
     * Booking iptal akışında 24 saat öncesi ise refund tetikle.
     *
     * @return array{ok:bool,refund_id:?string,reason:?string}
     */
    public function createRefund(PublicBooking $booking, string $reason = ''): array
    {
        $this->bootStripe();

        if ($booking->payment_status !== 'paid') {
            return ['ok' => false, 'refund_id' => null, 'reason' => 'Booking ödenmemiş — iade yapılamaz.'];
        }
        if (empty($booking->stripe_payment_intent_id)) {
            return ['ok' => false, 'refund_id' => null, 'reason' => 'Stripe payment_intent eksik.'];
        }

        try {
            $refund = StripeRefund::create([
                'payment_intent' => $booking->stripe_payment_intent_id,
                'reason'         => 'requested_by_customer',
                'metadata'       => [
                    'source'        => 'mentorde_booking',
                    'booking_id'    => (string) $booking->id,
                    'booking_token' => $booking->booking_token,
                    'cancel_reason' => mb_substr($reason ?: '-', 0, 240),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Stripe refund failed', [
                'booking_id' => $booking->id,
                'error'      => $e->getMessage(),
            ]);
            return ['ok' => false, 'refund_id' => null, 'reason' => 'Stripe iade hatası: ' . $e->getMessage()];
        }

        DB::transaction(function () use ($booking, $refund) {
            $booking->update([
                'payment_status' => 'refunded',
                'refunded_at'    => now(),
                'refund_id'      => (string) $refund->id,
            ]);
            $this->reverseRelatedEarnings($booking);
        });

        return ['ok' => true, 'refund_id' => (string) $refund->id, 'reason' => null];
    }

    // ── Event handlers ────────────────────────────────────────────────────

    /**
     * checkout.session.completed → ödeme alındı. Booking + appointment + earning oluştur.
     */
    private function onSessionCompleted(PublicBooking $booking, $session): array
    {
        if ($booking->isPaid()) {
            // Idempotent — Stripe webhook'u retry edebilir
            return ['status' => 'already_paid', 'booking_id' => $booking->id, 'message' => 'no-op'];
        }

        $paymentIntentId = (string) ($session->payment_intent ?? '');

        DB::transaction(function () use ($booking, $paymentIntentId) {
            $booking->update([
                'payment_status'           => 'paid',
                'paid_at'                  => now(),
                'status'                   => 'confirmed',
                'stripe_payment_intent_id' => $paymentIntentId ?: $booking->stripe_payment_intent_id,
            ]);

            // Booking confirmed olduğuna göre student_appointments + earnings yazmalıyız.
            // BookingConfirmationService::confirm() bunu yapıyor ama biz pending_payment
            // path'inde geçtiğimiz için manuel olarak burada tetikliyoruz.
            $this->materializeBookingArtifacts($booking);
        });

        return ['status' => 'paid', 'booking_id' => $booking->id, 'message' => 'confirmed'];
    }

    /**
     * checkout.session.expired → 15 dakika hold süresi doldu, kullanıcı ödeme yapmadı.
     */
    private function onSessionExpired(PublicBooking $booking, $session): array
    {
        if ($booking->isPaid() || $booking->isRefunded()) {
            return ['status' => 'already_settled', 'booking_id' => $booking->id, 'message' => 'no-op'];
        }
        if (!$booking->isPendingPayment()) {
            return ['status' => 'not_pending', 'booking_id' => $booking->id, 'message' => 'no-op'];
        }

        $booking->update([
            'payment_status' => 'failed',
            'status'         => 'canceled_by_invitee',
            'canceled_at'    => now(),
            'payment_failure_reason' => 'Ödeme süresi (15 dakika) doldu.',
        ]);

        return ['status' => 'expired', 'booking_id' => $booking->id, 'message' => 'canceled'];
    }

    /**
     * charge.refunded / payment_intent.refunded → Stripe panelinden manuel iade.
     * Webhook ile gelirse de earning'i ters çevir (refund createRefund() üzerinden
     * yapıldıysa zaten reversed, idempotent).
     */
    private function onChargeRefunded(PublicBooking $booking, $object): array
    {
        if ($booking->isRefunded()) {
            return ['status' => 'already_refunded', 'booking_id' => $booking->id, 'message' => 'no-op'];
        }

        DB::transaction(function () use ($booking, $object) {
            $refundId = $this->extractRefundId($object);
            $booking->update([
                'payment_status' => 'refunded',
                'refunded_at'    => now(),
                'refund_id'      => $refundId ?: $booking->refund_id,
                'status'         => 'canceled_by_invitee',
                'canceled_at'    => $booking->canceled_at ?: now(),
            ]);
            $this->reverseRelatedEarnings($booking);
        });

        return ['status' => 'refunded', 'booking_id' => $booking->id, 'message' => 'earnings reversed'];
    }

    /**
     * payment_intent.payment_failed / async_payment_failed → ödeme reddedildi.
     */
    private function onPaymentFailed(PublicBooking $booking, $object): array
    {
        if ($booking->isPaid()) {
            // Önce paid sonra failed — Stripe race condition; paid'i bırak
            return ['status' => 'paid_overrides_failed', 'booking_id' => $booking->id, 'message' => 'no-op'];
        }

        $errMsg = (string) ($object->last_payment_error->message ?? 'Ödeme başarısız.');
        $booking->update([
            'payment_status'         => 'failed',
            'payment_failure_reason' => mb_substr($errMsg, 0, 250),
        ]);

        return ['status' => 'failed', 'booking_id' => $booking->id, 'message' => $errMsg];
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    /**
     * pending_payment booking için Stripe Checkout completed olduğunda
     * student_appointment + senior_earning kaydı oluştur.
     */
    private function materializeBookingArtifacts(PublicBooking $booking): void
    {
        // Zaten appointment varsa idempotent skip
        if ($booking->student_appointment_id) {
            return;
        }

        $settings = SeniorBookingSetting::query()
            ->withoutGlobalScopes()
            ->where('senior_user_id', $booking->senior_user_id)
            ->first();

        if (!$settings) {
            Log::warning('materializeBookingArtifacts: senior settings not found', [
                'booking_id' => $booking->id,
                'senior_id'  => $booking->senior_user_id,
            ]);
            return;
        }

        $senior = User::query()
            ->withoutGlobalScopes()
            ->where('id', $booking->senior_user_id)
            ->first();

        $startsAt = CarbonImmutable::parse($booking->starts_at);

        $appointment = StudentAppointment::create([
            'company_id'       => $booking->company_id,
            'student_id'       => $this->deriveStudentId($booking),
            'student_email'    => $booking->invitee_email,
            'senior_email'     => $senior?->email,
            'title'            => $settings->display_name ?: 'Randevu',
            'note'             => $this->buildAppointmentNote($booking),
            'scheduled_at'     => $startsAt->setTimezone('UTC'),
            'duration_minutes' => (int) $settings->slot_duration,
            'channel'          => 'online',
            'status'           => 'scheduled',
        ]);

        $booking->update(['student_appointment_id' => $appointment->id]);

        // Earning kaydı — Phase 5: cents schema kullan, status=recorded (24 saat sonra available)
        try {
            $netCents     = (int) $booking->amount_net_cents;
            $grossCents   = (int) $booking->amount_gross_cents;
            $taxCents     = (int) $booking->tax_amount_cents;
            $commissionPct = $this->loadCommissionPct($booking->company_id);
            $commissionCents = (int) round($netCents * $commissionPct / 100);
            $payoutCents     = max(0, $netCents - $commissionCents);

            SeniorEarning::create([
                'company_id'             => $booking->company_id,
                'senior_user_id'         => $booking->senior_user_id,
                'public_booking_id'      => $booking->id,
                'student_appointment_id' => $appointment->id,
                'amount_net_cents'       => $netCents,
                'tax_rate_pct_applied'   => (string) $booking->tax_rate_pct_applied,
                'tax_amount_cents'       => $taxCents,
                'amount_gross_cents'     => $grossCents,
                'commission_pct_applied' => (string) $commissionPct,
                'commission_cents'       => $commissionCents,
                'senior_payout_cents'    => $payoutCents,
                'currency'               => (string) ($booking->currency ?: 'EUR'),
                'status'                 => SeniorEarning::STATUS_RECORDED,
                'recorded_at'            => now(),
                'stripe_charge_id'       => $this->extractChargeId($booking),
            ]);
        } catch (\Throwable $e) {
            Log::warning('SeniorEarning create failed in materializeBookingArtifacts', [
                'booking_id' => $booking->id,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    /**
     * Bu booking'e bağlı earning'leri reverse et (refund sonrası).
     * Paid_out olanlar dokunulmaz (Phase 6 payout reversal akışı ayrı).
     */
    private function reverseRelatedEarnings(PublicBooking $booking): void
    {
        $earnings = SeniorEarning::query()
            ->withoutGlobalScopes()
            ->where('public_booking_id', $booking->id)
            ->whereIn('status', [SeniorEarning::STATUS_RECORDED, SeniorEarning::STATUS_AVAILABLE])
            ->get();

        foreach ($earnings as $earning) {
            $earning->markRefunded();
        }
    }

    private function bootStripe(): void
    {
        $secret = (string) config('services.stripe.secret');
        if ($secret === '') {
            throw new \DomainException('Stripe secret key ayarlı değil (config services.stripe.secret).');
        }
        Stripe::setApiKey($secret);
    }

    private function extractBookingId($object): ?int
    {
        if (!$object) return null;

        // Checkout Session
        $metadata = $object->metadata ?? null;
        if ($metadata) {
            $source = (string) ($metadata->source ?? '');
            if ($source && $source !== 'mentorde_booking') {
                return null; // Student payment veya başka kaynak
            }
            $bid = $metadata->booking_id ?? null;
            if ($bid !== null) {
                return (int) $bid;
            }
        }

        // PaymentIntent veya Charge → metadata burada da olabilir
        if (isset($object->metadata->booking_id)) {
            return (int) $object->metadata->booking_id;
        }

        return null;
    }

    private function extractRefundId($object): ?string
    {
        // charge.refunded payload'ında latest refund ID
        if (isset($object->refunds->data[0]->id)) {
            return (string) $object->refunds->data[0]->id;
        }
        if (isset($object->id) && str_starts_with((string) $object->id, 're_')) {
            return (string) $object->id;
        }
        return null;
    }

    private function extractChargeId(PublicBooking $booking): ?string
    {
        if (empty($booking->stripe_payment_intent_id)) {
            return null;
        }
        try {
            $intent = \Stripe\PaymentIntent::retrieve($booking->stripe_payment_intent_id);
            if (isset($intent->latest_charge)) {
                return (string) $intent->latest_charge;
            }
        } catch (\Throwable $e) {
            Log::info('PaymentIntent retrieve failed for charge_id', [
                'booking_id' => $booking->id,
                'error'      => $e->getMessage(),
            ]);
        }
        return null;
    }

    private function deriveStudentId(PublicBooking $booking): string
    {
        if ($booking->student_user_id) {
            $u = User::query()->withoutGlobalScopes()->where('id', $booking->student_user_id)->first(['student_id']);
            if ($u && !empty($u->student_id)) {
                return (string) $u->student_id;
            }
        }
        if ($booking->guest_application_id) {
            return 'GUEST-' . $booking->guest_application_id;
        }
        return 'PB-' . str_pad((string) $booking->id, 8, '0', STR_PAD_LEFT);
    }

    private function buildAppointmentNote(PublicBooking $booking): string
    {
        $parts = [
            "Public Booking (paid): {$booking->invitee_name} <{$booking->invitee_email}>",
            sprintf('Tutar: %.2f %s', $booking->amount_gross_cents / 100, $booking->currency ?: 'EUR'),
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

    private function buildSessionDescription(PublicBooking $booking): string
    {
        try {
            $tz   = config('app.timezone', 'Europe/Berlin');
            $when = CarbonImmutable::parse($booking->starts_at)->setTimezone($tz)->format('d.m.Y H:i');
            return "Randevu: {$when} (45 dk)";
        } catch (\Throwable) {
            return 'Online görüşme';
        }
    }

    private function loadCommissionPct(int $companyId): float
    {
        $cps = \App\Models\CompanyPaymentSetting::query()
            ->withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->first(['default_commission_pct']);
        return (float) ($cps?->default_commission_pct ?? 20.0);
    }
}
