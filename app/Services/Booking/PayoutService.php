<?php

namespace App\Services\Booking;

use App\Models\Company;
use App\Models\PayoutSetting;
use App\Models\SeniorEarning;
use App\Models\SeniorPayout;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Marketplace Phase 6 — Senior payout orkestrasyonu
 *
 * Sorumluluklar:
 *   1. Bir aylık periyot için senior'ların eligible kazançlarını topla
 *   2. SeniorPayout kayıtları oluştur (status=pending) + earnings'i payout_id ile bağla
 *   3. Stripe transfer çalıştır (mevcut Stripe SDK kullanır)
 *   4. On-demand ödeme talebi (allow_on_demand=true company'lerde, aylık 2 limit)
 *
 * Para birimi: senior_earnings'te cent (integer) saklanıyor → tüm SUM/calc cent'te.
 * Eşik kontrolü: payout_minimum_eur DECIMAL → cent'e çevrilir (×100).
 */
class PayoutService
{
    public const ON_DEMAND_MONTHLY_LIMIT = 2;

    /**
     * Bir ay için, şirket bazında senior başına eligible payout adayı hesapla.
     *
     * @param  Carbon  $month     Ayın herhangi bir günü (startOfMonth/endOfMonth alınır)
     * @param  int     $companyId
     * @return array<int,array{senior_user_id:int,amount_cents:int,earning_ids:array<int,int>,currency:string}>
     */
    public function calculateMonthlyPayouts(Carbon $month, int $companyId): array
    {
        $start    = $month->copy()->startOfMonth()->startOfDay();
        $end      = $month->copy()->endOfMonth()->endOfDay();
        $settings = PayoutSetting::forCompany($companyId);
        $minCents = (int) round(((float) $settings->payout_minimum_eur) * 100);

        // 'available' status = Phase 5 işlemden geçirdi, payout'a hazır.
        // Phase 4/5 farklı status string'leri kullanabiliyor → 'recorded' da kabul (henüz settle olmamış paid kazanç).
        $eligibleStatuses = ['available', 'recorded'];

        $earnings = SeniorEarning::query()
            ->withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereIn('status', $eligibleStatuses)
            ->whereNull('payout_id')
            ->whereBetween('recorded_at', [$start, $end])
            ->get();

        $bySenior = $earnings->groupBy('senior_user_id');

        $candidates = [];
        foreach ($bySenior as $seniorId => $rows) {
            $totalCents = (int) $rows->sum('senior_payout_cents');
            if ($totalCents < $minCents) {
                continue;
            }
            $candidates[] = [
                'senior_user_id' => (int) $seniorId,
                'amount_cents'   => $totalCents,
                'earning_ids'    => $rows->pluck('id')->map(fn($v) => (int) $v)->all(),
                'currency'       => (string) ($rows->first()->currency ?? $settings->currency ?? 'EUR'),
            ];
        }

        return $candidates;
    }

    /**
     * calculateMonthlyPayouts sonucunu kullanarak SeniorPayout kayıtları oluştur.
     * Her earning row'unu payout_id ile bağlar.
     *
     * @return Collection<int,SeniorPayout>
     */
    public function createPayouts(Carbon $month, int $companyId): Collection
    {
        $candidates = $this->calculateMonthlyPayouts($month, $companyId);
        if (empty($candidates)) {
            return collect();
        }

        $start = $month->copy()->startOfMonth()->toDateString();
        $end   = $month->copy()->endOfMonth()->toDateString();

        $created = collect();

        DB::transaction(function () use ($candidates, $companyId, $start, $end, &$created): void {
            foreach ($candidates as $c) {
                $payout = SeniorPayout::query()->create([
                    'company_id'     => $companyId,
                    'senior_user_id' => $c['senior_user_id'],
                    'amount_cents'   => $c['amount_cents'],
                    'currency'       => $c['currency'],
                    'period_start'   => $start,
                    'period_end'     => $end,
                    'status'         => 'pending',
                    'method'         => 'stripe',
                    'requested_at'   => now(),
                ]);

                SeniorEarning::query()
                    ->withoutGlobalScopes()
                    ->whereIn('id', $c['earning_ids'])
                    ->update(['payout_id' => $payout->id]);

                $created->push($payout);
            }
        });

        return $created;
    }

    /**
     * status='pending' payout'ları Stripe\Transfer::create ile gönder.
     *
     * @return array{success:int,failed:int,errors:array<int,string>}
     */
    public function processStripeTransfers(?int $companyId = null): array
    {
        $query = SeniorPayout::query()->withoutGlobalScopes()->where('status', 'pending');
        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $pendings = $query->get();
        $success  = 0;
        $failed   = 0;
        $errors   = [];

        $stripeKey = (string) (config('services.stripe.secret') ?? env('STRIPE_SECRET') ?? '');
        $stripeAvailable = class_exists(\Stripe\Stripe::class) && $stripeKey !== '';

        foreach ($pendings as $payout) {
            try {
                if (!$stripeAvailable) {
                    // Stripe yapılandırılmamış — failed olarak işaretle ki manager retry görebilsin.
                    $this->markFailed($payout, 'Stripe credentials eksik (STRIPE_SECRET).');
                    $failed++;
                    $errors[$payout->id] = 'Stripe credentials missing';
                    continue;
                }

                $senior = User::query()->withoutGlobalScopes()->find($payout->senior_user_id);
                $stripeAccountId = (string) ($senior?->stripe_connect_account_id ?? '');
                if ($stripeAccountId === '') {
                    $this->markFailed($payout, 'Senior Stripe Connect hesabı bağlı değil.');
                    $failed++;
                    $errors[$payout->id] = 'Senior has no Stripe Connect account';
                    continue;
                }

                $payout->update([
                    'status' => 'processing',
                ]);

                \Stripe\Stripe::setApiKey($stripeKey);
                $transfer = \Stripe\Transfer::create([
                    'amount'      => (int) $payout->amount_cents,
                    'currency'    => strtolower((string) $payout->currency),
                    'destination' => $stripeAccountId,
                    'description' => sprintf(
                        'MentorDE Senior Payout #%d (%s → %s)',
                        $payout->id,
                        (string) $payout->period_start,
                        (string) $payout->period_end
                    ),
                    'metadata' => [
                        'mentorde_payout_id' => (string) $payout->id,
                        'company_id'         => (string) $payout->company_id,
                        'senior_user_id'     => (string) $payout->senior_user_id,
                    ],
                ]);

                $payout->update([
                    'status'             => 'paid',
                    'stripe_transfer_id' => $transfer->id ?? null,
                    'paid_at'            => now(),
                    'failure_reason'     => null,
                ]);

                // earnings status güncelle
                SeniorEarning::query()
                    ->withoutGlobalScopes()
                    ->where('payout_id', $payout->id)
                    ->update(['status' => 'paid_out']);

                $success++;
            } catch (\Throwable $e) {
                $this->markFailed($payout, $e->getMessage());
                $failed++;
                $errors[$payout->id] = $e->getMessage();
                Log::warning('payout.stripe_transfer_failed', [
                    'payout_id' => $payout->id,
                    'error'     => $e->getMessage(),
                ]);
            }
        }

        return [
            'success' => $success,
            'failed'  => $failed,
            'errors'  => $errors,
        ];
    }

    /**
     * Senior on-demand ödeme talebi.
     * Koşullar:
     *   - payout_settings.allow_on_demand=true
     *   - Senior'un eligible earnings'i payout_minimum_eur eşiğini geçmeli
     *   - Aylık on-demand limiti: 2
     *
     * Range: senior'un son payout period_end'inden bugüne kadar (varsa), yoksa cari ay başından bugüne.
     */
    public function requestOnDemand(int $seniorUserId): ?SeniorPayout
    {
        $senior = User::query()->withoutGlobalScopes()->find($seniorUserId);
        if (!$senior) {
            return null;
        }
        $companyId = (int) $senior->company_id;
        if ($companyId <= 0) {
            return null;
        }

        $settings = PayoutSetting::forCompany($companyId);
        if (!$settings->allow_on_demand) {
            return null;
        }

        // Aylık limit kontrolü — bu ay içinde request edilen on-demand payout sayısı
        $thisMonthStart = now()->startOfMonth();
        $onDemandCount  = SeniorPayout::query()
            ->withoutGlobalScopes()
            ->where('senior_user_id', $seniorUserId)
            ->where('requested_at', '>=', $thisMonthStart)
            ->whereIn('status', ['pending', 'processing', 'paid'])
            ->whereNotNull('requested_at')
            // On-demand'i ayırt etmek için method='stripe_on_demand' kullanıyoruz
            ->where('method', 'stripe_on_demand')
            ->count();

        if ($onDemandCount >= self::ON_DEMAND_MONTHLY_LIMIT) {
            return null;
        }

        $lastPayoutEnd = SeniorPayout::query()
            ->withoutGlobalScopes()
            ->where('senior_user_id', $seniorUserId)
            ->orderByDesc('period_end')
            ->value('period_end');

        $rangeStart = $lastPayoutEnd
            ? Carbon::parse($lastPayoutEnd)->addDay()->startOfDay()
            : now()->startOfMonth();
        $rangeEnd   = now()->endOfDay();

        $minCents = (int) round(((float) $settings->payout_minimum_eur) * 100);

        $earnings = SeniorEarning::query()
            ->withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('senior_user_id', $seniorUserId)
            ->whereIn('status', ['available', 'recorded'])
            ->whereNull('payout_id')
            ->whereBetween('recorded_at', [$rangeStart, $rangeEnd])
            ->get();

        $totalCents = (int) $earnings->sum('senior_payout_cents');
        if ($totalCents < $minCents) {
            return null;
        }

        $payout = null;
        DB::transaction(function () use ($earnings, $totalCents, $companyId, $seniorUserId, $rangeStart, $rangeEnd, $settings, &$payout): void {
            $payout = SeniorPayout::query()->create([
                'company_id'     => $companyId,
                'senior_user_id' => $seniorUserId,
                'amount_cents'   => $totalCents,
                'currency'       => (string) ($earnings->first()->currency ?? $settings->currency ?? 'EUR'),
                'period_start'   => $rangeStart->toDateString(),
                'period_end'     => $rangeEnd->toDateString(),
                'status'         => 'pending',
                'method'         => 'stripe_on_demand',
                'requested_at'   => now(),
                'notes'          => 'On-demand talep',
            ]);

            SeniorEarning::query()
                ->withoutGlobalScopes()
                ->whereIn('id', $earnings->pluck('id')->all())
                ->update(['payout_id' => $payout->id]);
        });

        return $payout;
    }

    /**
     * Bir failed/cancelled payout'u tekrar processable hale getir + Stripe deneme.
     */
    public function retryPayout(SeniorPayout $payout): array
    {
        if (!in_array($payout->status, ['failed', 'cancelled'], true)) {
            return ['success' => 0, 'failed' => 1, 'errors' => [$payout->id => 'Sadece failed/cancelled payout retry edilebilir.']];
        }
        $payout->update(['status' => 'pending', 'failure_reason' => null]);
        return $this->processStripeTransfers((int) $payout->company_id);
    }

    private function markFailed(SeniorPayout $payout, string $reason): void
    {
        $payout->update([
            'status'         => 'failed',
            'failure_reason' => mb_substr($reason, 0, 2000),
        ]);
    }
}
