<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiscountCode extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'code',
        'description',
        'discount_type',
        'discount_value',
        'max_redemptions',
        'max_per_user',
        'valid_from',
        'valid_until',
        'applies_to_package_codes',
        'min_purchase_amount_eur',
        'dealer_id',
        'metadata',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'discount_value'           => 'decimal:2',
        'min_purchase_amount_eur'  => 'decimal:2',
        'max_redemptions'          => 'integer',
        'redemption_count'         => 'integer',
        'max_per_user'             => 'integer',
        'is_active'                => 'boolean',
        'valid_from'               => 'datetime',
        'valid_until'              => 'datetime',
        'applies_to_package_codes' => 'array',
        'metadata'                 => 'array',
    ];

    public function redemptions(): HasMany
    {
        return $this->hasMany(DiscountCodeRedemption::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Verilen tutara bu kodun uygulayacağı indirimi hesaplar.
     */
    public function computeDiscount(float $amount): float
    {
        if ($this->discount_type === 'percent') {
            $pct = max(0, min(100, (float) $this->discount_value));
            return round($amount * $pct / 100, 2);
        }
        // fixed
        $val = (float) $this->discount_value;
        return round(min($val, $amount), 2); // negatife düşmesin
    }

    public function isCurrentlyActive(): bool
    {
        if (! $this->is_active) return false;
        $now = now();
        if ($this->valid_from && $now->lt($this->valid_from)) return false;
        if ($this->valid_until && $now->gt($this->valid_until)) return false;
        if ($this->max_redemptions !== null && $this->redemption_count >= $this->max_redemptions) return false;
        return true;
    }
}
