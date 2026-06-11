<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Marketplace Phase 6 — şirket ödeme akışı ayarları
 *
 * 1:1 company. Cache'li singleton lookup pattern (forCompany).
 */
class PayoutSetting extends Model
{
    protected $table = 'payout_settings';

    protected $fillable = [
        'company_id',
        'payout_day',
        'payout_minimum_eur',
        'allow_on_demand',
        'currency',
        'notification_email',
    ];

    protected $casts = [
        'company_id'         => 'integer',
        'payout_day'         => 'integer',
        'payout_minimum_eur' => 'decimal:2',
        'allow_on_demand'    => 'boolean',
    ];

    public const CACHE_TTL_SECONDS = 600;     // 10dk

    public static function cacheKey(int $companyId): string
    {
        return "payout_settings:company:{$companyId}:v1";
    }

    /**
     * Şirket için ayarları getir (yoksa varsayılan in-memory instance döner — kayıt yaratmaz).
     */
    public static function forCompany(int $companyId): self
    {
        return Cache::remember(
            self::cacheKey($companyId),
            self::CACHE_TTL_SECONDS,
            function () use ($companyId): self {
                $row = self::query()->where('company_id', $companyId)->first();
                if ($row) {
                    return $row;
                }
                return (new self([
                    'company_id'         => $companyId,
                    'payout_day'         => 5,
                    'payout_minimum_eur' => 100.00,
                    'allow_on_demand'    => true,
                    'currency'           => 'EUR',
                    'notification_email' => null,
                ]));
            }
        );
    }

    public static function forgetCacheFor(int $companyId): void
    {
        Cache::forget(self::cacheKey($companyId));
    }

    protected static function booted(): void
    {
        static::saved(function (self $m): void {
            self::forgetCacheFor((int) $m->company_id);
        });
        static::deleted(function (self $m): void {
            self::forgetCacheFor((int) $m->company_id);
        });
    }
}
