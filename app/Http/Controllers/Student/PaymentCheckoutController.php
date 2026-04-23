<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Student\Concerns\StudentPortalTrait;
use App\Models\StudentPayment;
use App\Models\StudentRevenue;
use App\Services\CurrencyRateService;
use App\Support\SchemaCache;
use Illuminate\Http\Request;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Stripe;

class PaymentCheckoutController extends Controller
{
    use StudentPortalTrait;

    /**
     * Öğrencinin ödeme durumu ve fatura listesi.
     */
    public function index(Request $request)
    {
        $base      = $this->baseData($request, 'payments', 'Odeme Durumum', 'Paket ucreti, odeme durumu ve taksit takibi.');
        $studentId = (string) ($base['studentId'] ?? '');

        $revenue = null;
        if ($studentId !== '' && SchemaCache::hasTable('student_revenues')) {
            $revenue = StudentRevenue::query()
                ->where('student_id', $studentId)
                ->first(['id', 'student_id', 'package_total_price', 'total_earned', 'total_pending', 'total_remaining', 'milestone_progress', 'updated_at']);
        }

        $currencyService = app(CurrencyRateService::class);
        $eurTryRate      = $currencyService->getRate('EUR', 'TRY');
        $eurTryRateDate  = $currencyService->getRateDate('EUR', 'TRY');

        $invoices = $studentId !== '' && SchemaCache::hasTable('student_payments')
            ? StudentPayment::where('student_id', $studentId)->orderByDesc('due_date')->get()
            : collect();

        return view('student.payments', array_merge($base, [
            'revenue'        => $revenue,
            'eurTryRate'     => $eurTryRate,
            'eurTryRateDate' => $eurTryRateDate,
            'invoices'       => $invoices,
        ]));
    }

    /**
     * Stripe Checkout oturumu oluştur ve öğrenciyi Stripe sayfasına yönlendir.
     */
    public function checkout(Request $request, int $paymentId)
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $user    = $request->user();
        $payment = StudentPayment::where('id', $paymentId)
            ->where('student_id', $user->student_id)
            ->whereIn('status', ['pending', 'overdue'])
            ->firstOrFail();

        // Halihazırda bir Stripe oturumu varsa direkt oraya yönlendir
        if ($payment->stripe_session_id) {
            try {
                $session = StripeSession::retrieve($payment->stripe_session_id);
                if ($session->status === 'open') {
                    return redirect($session->url);
                }
            } catch (\Exception) {
                // Eski oturum geçersiz — yenisini oluştur
            }
        }

        $amountCents = (int) round((float) $payment->amount_eur * 100);
        $currency    = strtolower($payment->currency ?? 'eur');

        $session = StripeSession::create([
            'mode'                 => 'payment',
            'payment_method_types' => ['card'],
            'line_items'           => [[
                'price_data' => [
                    'currency'     => $currency,
                    'unit_amount'  => $amountCents,
                    'product_data' => [
                        'name'        => "MentorDE — {$payment->invoice_number}",
                        'description' => $payment->description ?? 'Eğitim hizmet bedeli',
                    ],
                ],
                'quantity' => 1,
            ]],
            'metadata' => [
                'payment_id'  => $payment->id,
                'student_id'  => $user->student_id,
                'invoice_no'  => $payment->invoice_number,
            ],
            'success_url' => url('/student/payments?stripe=success&inv=' . $payment->invoice_number),
            'cancel_url'  => url('/student/payments?stripe=cancelled'),
        ]);

        $payment->update(['stripe_session_id' => $session->id]);

        return redirect($session->url);
    }

    /**
     * Stripe webhook — ödeme başarılı olduğunda DB'yi güncelle.
     * Route: POST /webhooks/stripe (auth gerektirmez, imza kontrolü var)
     */
    public static function handleWebhook(Request $request): \Illuminate\Http\Response
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $payload   = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $secret    = config('services.stripe.webhook_secret');

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return response('Webhook imzası geçersiz.', 400);
        }

        if ($event->type === 'checkout.session.completed') {
            $session   = $event->data->object;
            $paymentId = $session->metadata->payment_id ?? null;

            if ($paymentId) {
                $payment = StudentPayment::where('id', $paymentId)
                    ->whereIn('status', ['pending', 'overdue'])
                    ->first();

                if ($payment) {
                    $payment->update([
                        'status'                    => 'paid',
                        'paid_at'                   => now(),
                        'payment_method'            => 'stripe',
                        'stripe_payment_intent_id'  => $session->payment_intent,
                    ]);

                    // PostHog: payment_succeeded
                    try {
                        app(\App\Services\Analytics\AnalyticsService::class)->capture(
                            'payment_succeeded',
                            [
                                'payment_id'   => $payment->id,
                                'amount_eur'   => (float) $payment->amount_eur,
                                'currency'     => $payment->currency ?? 'EUR',
                                'payment_method' => 'stripe',
                                'invoice_number' => $payment->invoice_number,
                                'company_id'   => $payment->company_id,
                            ],
                            (string) $payment->student_id
                        );
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning('PostHog payment_succeeded capture failed', ['error' => $e->getMessage()]);
                    }
                }
            }
        }

        // Stripe webhook: payment failure
        if ($event->type === 'checkout.session.async_payment_failed' || $event->type === 'payment_intent.payment_failed') {
            $object = $event->data->object;
            $paymentId = $object->metadata->payment_id ?? null;

            if ($paymentId) {
                $payment = StudentPayment::find($paymentId);
                if ($payment) {
                    try {
                        app(\App\Services\Analytics\AnalyticsService::class)->capture(
                            'payment_failed',
                            [
                                'payment_id'       => $payment->id,
                                'amount_eur'       => (float) $payment->amount_eur,
                                'currency'         => $payment->currency ?? 'EUR',
                                'failure_code'     => $object->last_payment_error->code ?? null,
                                'failure_message'  => $object->last_payment_error->message ?? null,
                                'company_id'       => $payment->company_id,
                            ],
                            (string) $payment->student_id
                        );
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning('PostHog payment_failed capture failed', ['error' => $e->getMessage()]);
                    }
                }
            }
        }

        return response('OK', 200);
    }
}
