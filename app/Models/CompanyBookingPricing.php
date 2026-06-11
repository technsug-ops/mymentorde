<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Marketplace Phase 4 — Manager Pricing Cockpit v1
 *
 * pricing_rules JSON şeması (ileri-geri uyumlu):
 *   [{"duration": 15|30|45|60|90|120, "price_net": 30.00, "enabled": true}, ...]
 *
 * Form geçici alanları `price_eur` adıyla gelebilir; controller normalize eder
 * → DB'de her zaman `price_net` saklanır (PricingResolver bu key'i bekliyor).
 */
class CompanyBookingPricing extends Model
{
    use BelongsToCompany;

    protected $table = 'company_booking_pricing';

    protected $fillable = [
        'company_id',
        'is_free',
        'currency',
        'cancellation_window_hours',
        'allow_invitee_reschedule',
        'max_advance_booking_days',
        'booking_terms',
        'pricing_rules',
    ];

    protected $casts = [
        'is_free'                   => 'boolean',
        'allow_invitee_reschedule'  => 'boolean',
        'cancellation_window_hours' => 'integer',
        'max_advance_booking_days'  => 'integer',
        'pricing_rules'             => 'array',
    ];

    /** Cache key (company başına singleton). */
    public static function cacheKey(int $companyId): string
    {
        return "company_booking_pricing:{$companyId}";
    }

    /** Cache'i temizle — controller update sonrası çağırır. */
    public static function flushCache(int $companyId): void
    {
        Cache::forget(self::cacheKey($companyId));
    }

    /** Varsayılan fiyat tablosu — migration'da ve form'da kullanılır. */
    public static function defaultRules(): array
    {
        return [
            ['duration' => 15,  'price_net' => 15,  'enabled' => false],
            ['duration' => 30,  'price_net' => 30,  'enabled' => true],
            ['duration' => 45,  'price_net' => 40,  'enabled' => true],
            ['duration' => 60,  'price_net' => 50,  'enabled' => true],
            ['duration' => 90,  'price_net' => 70,  'enabled' => false],
            ['duration' => 120, 'price_net' => 90,  'enabled' => false],
        ];
    }

    /**
     * Company için singleton kayıt — yoksa default ile oluştur.
     * Cache'li; 60 sn TTL (cockpit dışı okumalar için).
     */
    public static function forCompany(int $companyId): self
    {
        /** @var self $pricing */
        $pricing = Cache::remember(
            self::cacheKey($companyId),
            60,
            fn () => self::query()
                ->withoutGlobalScopes()
                ->firstOrCreate(
                    ['company_id' => $companyId],
                    [
                        'is_free'                    => true,
                        'currency'                   => 'EUR',
                        'cancellation_window_hours'  => 24,
                        'allow_invitee_reschedule'   => true,
                        'max_advance_booking_days'   => 60,
                        'booking_terms'              => null,
                        'pricing_rules'              => self::defaultRules(),
                    ]
                )
        );

        return $pricing;
    }

    /** Verilen süre için net fiyat (cent cinsinden) — yoksa null. */
    public function priceNetCentsFor(int $durationMinutes): ?int
    {
        if ($this->is_free) {
            return 0;
        }
        foreach (($this->pricing_rules ?? []) as $rule) {
            if ((int) ($rule['duration'] ?? 0) === $durationMinutes) {
                if (!($rule['enabled'] ?? false)) {
                    return null;
                }
                return (int) round(((float) ($rule['price_net'] ?? 0)) * 100);
            }
        }
        return null;
    }

    /**
     * Süre için EUR/floating fiyat (UI öneri/önizleme için).
     * is_free → 0.0, devre dışı süre → null.
     */
    public function priceFor(int $durationMinutes): ?float
    {
        $cents = $this->priceNetCentsFor($durationMinutes);
        return $cents === null ? null : round($cents / 100, 2);
    }

    /** Aktif süreleri listele (senior settings + public landing dropdown). */
    public function availableDurations(): array
    {
        $rules = $this->pricing_rules ?? [];
        return collect($rules)
            ->where('enabled', true)
            ->pluck('duration')
            ->map(fn ($v) => (int) $v)
            ->values()
            ->all();
    }

    /** Geriye uyumluluk alias'ı. */
    public function enabledDurations(): array
    {
        return $this->availableDurations();
    }

    /**
     * Verilen randevu başlangıç zamanı, iptal penceresi içinde mi?
     * "Pencere içinde" = startsAt → şu an kadar olan saat farkı `cancellation_window_hours`'tan büyük.
     * Yani true dönerse iptal HALA mümkün, false dönerse pencere kapanmış.
     */
    public function isWithinCancellationWindow(DateTimeInterface $startsAt): bool
    {
        $hoursUntil = Carbon::now()->diffInHours(Carbon::instance(
            $startsAt instanceof \DateTime ? $startsAt : \DateTime::createFromInterface($startsAt)
        ), false);

        return $hoursUntil >= (int) ($this->cancellation_window_hours ?? 0);
    }
}
