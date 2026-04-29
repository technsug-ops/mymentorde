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
        'template_id',
        'landing_title',
        'landing_subtitle',
        'landing_cta_text',
        'landing_disclaimer',
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
        'template_id'              => 'integer',
        'is_active'                => 'boolean',
        'valid_from'               => 'datetime',
        'valid_until'              => 'datetime',
        'applies_to_package_codes' => 'array',
        'metadata'                 => 'array',
    ];

    /**
     * Hangi template'i kullanacak (1-5). Default: 1 (Classic).
     */
    public function effectiveTemplateId(): int
    {
        $id = (int) ($this->template_id ?? 1);
        return ($id >= 1 && $id <= 5) ? $id : 1;
    }

    public function discountText(): string
    {
        if ($this->discount_type === 'percent') {
            $val = rtrim(rtrim(number_format((float) $this->discount_value, 2, '.', ''), '0'), '.');
            return '%' . $val . ' İNDİRİM';
        }
        return number_format((float) $this->discount_value, 0, ',', '.') . ' EUR İNDİRİM';
    }

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
