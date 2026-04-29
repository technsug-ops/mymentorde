<?php

namespace App\Services;

use App\Models\Dealer;
use App\Models\DealerCommissionTier;
use App\Models\DealerStudentRevenue;

/**
 * Bayinin lifetime kümülatif kayıt sayısına göre tier belirleyici.
 * Pazarlama (/satis-ortagi) sayfasındaki matrisle birebir eşleşir.
 *
 * Ekstra feature olarak kurgulandı — module:dealer disable edilirse
 * sessizce default %7.5 / %20 / %10 oranlarına düşer (bkz. DealerType
 * default_commission_config), tier sistemi bypass.
 */
class DealerTierResolverService
{
    /**
     * Bayinin lifetime registered student count.
     * milestone_progress JSON'unda 'first_payment' → 'triggered' olan kayıtlar.
     */
    public function lifetimeRegistrationCount(Dealer $dealer): int
    {
        return DealerStudentRevenue::query()
            ->where('dealer_id', $dealer->id)
            ->count();
    }

    /**
     * Bayinin mevcut tier'ını belirle (count'a göre).
     * Hiç kayıt yoksa min_count=1 olan başlangıç tier'ı döner.
     */
    public function resolveFor(Dealer $dealer): ?DealerCommissionTier
    {
        $count = max(1, $this->lifetimeRegistrationCount($dealer));
        $type  = (string) ($dealer->dealer_type_code ?? '');
        if ($type === '') return null;

        return DealerCommissionTier::query()
            ->where('company_id', (int) $dealer->company_id)
            ->where('dealer_type_code', $type)
            ->where('is_active', true)
            ->where('min_count', '<=', $count)
            ->where(function ($q) use ($count) {
                $q->whereNull('max_count')->orWhere('max_count', '>=', $count);
            })
            ->orderByDesc('min_count')
            ->first();
    }

    /**
     * Sıradaki tier (yükselmek için).
     */
    public function nextTier(Dealer $dealer): ?DealerCommissionTier
    {
        $current = $this->resolveFor($dealer);
        if (! $current) return null;

        return DealerCommissionTier::query()
            ->where('company_id', $current->company_id)
            ->where('dealer_type_code', $current->dealer_type_code)
            ->where('is_active', true)
            ->where('min_count', '>', $current->min_count)
            ->orderBy('min_count')
            ->first();
    }

    /**
     * Progress bar verileri.
     *
     * @return array{
     *   current: ?DealerCommissionTier,
     *   next: ?DealerCommissionTier,
     *   count: int,
     *   to_next: int,
     *   percent: int,
     *   max_tier_reached: bool
     * }
     */
    public function progress(Dealer $dealer): array
    {
        $count   = $this->lifetimeRegistrationCount($dealer);
        $current = $this->resolveFor($dealer);
        $next    = $this->nextTier($dealer);

        if (! $current) {
            return [
                'current' => null, 'next' => null, 'count' => $count,
                'to_next' => 0, 'percent' => 0, 'max_tier_reached' => false,
            ];
        }

        if (! $next) {
            return [
                'current' => $current, 'next' => null, 'count' => $count,
                'to_next' => 0, 'percent' => 100, 'max_tier_reached' => true,
            ];
        }

        $toNext  = max(0, (int) $next->min_count - $count);
        $range   = max(1, (int) $next->min_count - (int) $current->min_count);
        $covered = max(0, $count - (int) $current->min_count);
        $percent = (int) min(100, round(($covered / $range) * 100));

        return [
            'current' => $current,
            'next'    => $next,
            'count'   => $count,
            'to_next' => $toNext,
            'percent' => $percent,
            'max_tier_reached' => false,
        ];
    }

    /**
     * Bayi'nin current_tier_code cache'ini günceller (DealerStudentRevenue
     * yazıldıktan sonra çağrılabilir). tier_unlocked_at değişti mi diye kontrol eder.
     */
    public function syncCache(Dealer $dealer): void
    {
        $tier = $this->resolveFor($dealer);
        if (! $tier) return;

        $changed = (string) $dealer->current_tier_code !== $tier->tier_code;
        $dealer->forceFill([
            'current_tier_code' => $tier->tier_code,
            'tier_unlocked_at'  => $changed ? now() : ($dealer->tier_unlocked_at ?? now()),
        ])->save();
    }
}
