<?php

namespace App\Services\Booking;

use App\Models\CommissionRule;
use Illuminate\Support\Facades\Cache;

/**
 * Marketplace Phase 6 — Komisyon yüzdesi çözücüsü
 *
 * Lookup sırası (eşit priority'de exact-match > tier-only > service-only > wildcard):
 *   1. Tier + Service eşleşmesi (her ikisi de set ve eşleşiyor)
 *   2. Sadece Tier eşleşmesi (service NULL)
 *   3. Sadece Service eşleşmesi (tier NULL)
 *   4. Wildcard kural (tier NULL + service NULL)
 *   5. Varsayılan: %20
 *
 * Sıralama: priority asc → düşük sayı önce.
 *
 * 10 dakikalık cache (company_id + tier + service kombinasyonu için).
 *
 * NOT: Mevcut PricingResolver `orderByDesc('priority')` kullanıyor; Phase 6'da
 * "düşük = öncelik" konvansiyonuna geçildi. PricingResolver davranışına dokunulmadı,
 * yeni servis CommissionResolver Phase 6 lookup pattern'iyle çalışır.
 */
class CommissionResolver
{
    public const CACHE_TTL_SECONDS = 600;
    public const DEFAULT_PCT       = 20.00;

    /**
     * Şirket + senior tier + service türü için komisyon yüzdesi döner.
     *
     * @param  int          $companyId
     * @param  string|null  $seniorTier      junior|mid|senior|expert (User::senior_type)
     * @param  string|null  $serviceType     consultation|document_review|mock_interview|pathway_planning
     * @return float                          %0-100 arası
     */
    public function resolveFor(int $companyId, ?string $seniorTier, ?string $serviceType): float
    {
        $tier    = $seniorTier ? trim($seniorTier) : null;
        $service = $serviceType ? trim($serviceType) : null;

        $cacheKey = sprintf(
            'commission_resolver:%d:%s:%s:v1',
            $companyId,
            $tier ?: '_',
            $service ?: '_'
        );

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($companyId, $tier, $service): float {
            $rules = CommissionRule::query()
                ->withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->where('is_active', true)
                ->orderBy('priority', 'asc')
                ->get();

            if ($rules->isEmpty()) {
                return self::DEFAULT_PCT;
            }

            // 1. Exact (tier + service)
            if ($tier && $service) {
                foreach ($rules as $r) {
                    if ($r->applies_to_tier === $tier && $r->applies_to_service_type === $service) {
                        return (float) $r->commission_pct;
                    }
                }
            }

            // 2. Tier-only (service NULL)
            if ($tier) {
                foreach ($rules as $r) {
                    if ($r->applies_to_tier === $tier && $r->applies_to_service_type === null) {
                        return (float) $r->commission_pct;
                    }
                }
            }

            // 3. Service-only (tier NULL)
            if ($service) {
                foreach ($rules as $r) {
                    if ($r->applies_to_tier === null && $r->applies_to_service_type === $service) {
                        return (float) $r->commission_pct;
                    }
                }
            }

            // 4. Wildcard
            foreach ($rules as $r) {
                if ($r->applies_to_tier === null && $r->applies_to_service_type === null) {
                    return (float) $r->commission_pct;
                }
            }

            return self::DEFAULT_PCT;
        });
    }

    public static function flushCacheForCompany(int $companyId): void
    {
        // Cache tag yok (file/database driver olabilir), pattern flush mümkün değil.
        // Pragmatik: TTL kısa (10dk), update sonrası UI'ya hafif gecikme normal.
        // İhtiyaç olursa tag destekli driver'da CommissionRule::saved'da `Cache::tags(['commission'])->flush()` eklenebilir.
        Cache::forget("commission_resolver:{$companyId}:_:_:v1");
    }
}
