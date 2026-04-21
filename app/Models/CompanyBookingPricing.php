<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class CompanyBookingPricing extends Model
{
    use BelongsToCompany;

    protected $table = 'company_booking_pricing';

    protected $fillable = [
        'company_id',
        'is_free',
        'currency',
        'cancellation_window_hours',
        'pricing_rules',
    ];

    protected $casts = [
        'is_free'                   => 'boolean',
        'cancellation_window_hours' => 'integer',
        'pricing_rules'             => 'array',
    ];

    /** Varsayılan fiyat tablosu — migration'da da kullanıldı. */
    public static function defaultRules(): array
    {
        return [
            ['duration' => 15, 'price_net' => 0, 'enabled' => false],
            ['duration' => 30, 'price_net' => 0, 'enabled' => true],
            ['duration' => 45, 'price_net' => 0, 'enabled' => true],
            ['duration' => 60, 'price_net' => 0, 'enabled' => true],
            ['duration' => 90, 'price_net' => 0, 'enabled' => false],
            ['duration' => 120, 'price_net' => 0, 'enabled' => false],
        ];
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

    /** Aktif süreleri listele (senior settings için dropdown). */
    public function enabledDurations(): array
    {
        $rules = $this->pricing_rules ?? [];
        return collect($rules)
            ->where('enabled', true)
            ->pluck('duration')
            ->map(fn ($v) => (int) $v)
            ->values()
            ->all();
    }
}
