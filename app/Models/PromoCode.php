<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Platform Owner — Promo Code.
 *
 * Tek bir indirim kodu. Tip percentage/fixed_amount/first_n_months_free.
 * isValid() = tarihi gecmis mi + max_uses asilmis mi + is_active mi.
 * canBeUsedBy(Company) = tier filtresi + zaten kullanmis mi (tek seferlik).
 * apply(Company) = current_uses++ + PromoCodeRedemption olusturur.
 */
class PromoCode extends Model
{
    public const TYPE_PERCENTAGE  = 'percentage';
    public const TYPE_FIXED       = 'fixed_amount';
    public const TYPE_FREE_MONTHS = 'first_n_months_free';

    public const TYPES = [
        self::TYPE_PERCENTAGE,
        self::TYPE_FIXED,
        self::TYPE_FREE_MONTHS,
    ];

    protected $fillable = [
        'code',
        'type',
        'value',
        'duration_months',
        'applies_to_tier',
        'max_uses',
        'current_uses',
        'valid_from',
        'valid_until',
        'is_active',
        'description',
        'created_by_user_id',
    ];

    protected $casts = [
        'value'           => 'decimal:2',
        'duration_months' => 'integer',
        'max_uses'        => 'integer',
        'current_uses'    => 'integer',
        'valid_from'      => 'date',
        'valid_until'     => 'date',
        'is_active'       => 'boolean',
    ];

    public function redemptions(): HasMany
    {
        return $this->hasMany(PromoCodeRedemption::class);
    }

    // ────────────────────────────────────────────────────────────────────────
    // VALIDATION
    // ────────────────────────────────────────────────────────────────────────

    /** Aktif + tarih araliginda + max_uses asilmamis mi? */
    public function isValid(?CarbonImmutable $now = null): bool
    {
        $now = $now ?? CarbonImmutable::now();

        if (!$this->is_active) return false;
        if ($this->valid_from && $this->valid_from->gt($now->toDateString())) return false;
        if ($this->valid_until && $this->valid_until->lt($now->toDateString())) return false;
        if ($this->max_uses !== null && $this->current_uses >= $this->max_uses) return false;

        return true;
    }

    /**
     * Bu company bu kodu kullanabilir mi?
     * - isValid olmali
     * - tier filtresi gecmeli (applies_to_tier NULL ise hepsine acik)
     * - company zaten kullanmamis olmali (tek bir company her kodu 1 kez kullanir)
     */
    public function canBeUsedBy(Company $company): bool
    {
        if (!$this->isValid()) return false;

        if ($this->applies_to_tier !== null
            && $this->applies_to_tier !== ''
            && $this->applies_to_tier !== $company->subscription_tier) {
            return false;
        }

        $alreadyRedeemed = PromoCodeRedemption::query()
            ->where('promo_code_id', $this->id)
            ->where('company_id', $company->id)
            ->exists();

        return !$alreadyRedeemed;
    }

    /**
     * Kodu company icin uygula:
     *   - PromoCodeRedemption kaydi olustur
     *   - current_uses++
     * Validation caller'in sorumlulugu — burada bos kayit acabilir,
     * o yuzden ServisLayer canBeUsedBy() once cagirmali.
     */
    public function apply(Company $company, float $discountEur = 0, array $invoiceIds = []): PromoCodeRedemption
    {
        $redemption = PromoCodeRedemption::create([
            'promo_code_id'        => $this->id,
            'company_id'           => $company->id,
            'applied_at'           => now(),
            'discount_applied_eur' => round($discountEur, 2),
            'invoice_ids'          => $invoiceIds,
        ]);

        $this->increment('current_uses');

        return $redemption;
    }

    // ────────────────────────────────────────────────────────────────────────
    // PRESENTATION
    // ────────────────────────────────────────────────────────────────────────

    public function typeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_PERCENTAGE  => 'Yüzde indirim',
            self::TYPE_FIXED       => 'Sabit EUR indirim',
            self::TYPE_FREE_MONTHS => 'İlk N ay ücretsiz',
            default                => ucfirst((string) $this->type),
        };
    }

    public function valueLabel(): string
    {
        return match ($this->type) {
            self::TYPE_PERCENTAGE  => rtrim(rtrim(number_format((float) $this->value, 2, ',', '.'), '0'), ',') . '%',
            self::TYPE_FIXED       => '€' . number_format((float) $this->value, 2, ',', '.'),
            self::TYPE_FREE_MONTHS => ($this->duration_months ?? 1) . ' ay ücretsiz',
            default                => (string) $this->value,
        };
    }

    /** UI status: active / expired / exhausted / inactive */
    public function uiStatus(): string
    {
        if (!$this->is_active) return 'inactive';
        if ($this->valid_until && $this->valid_until->isPast()) return 'expired';
        if ($this->max_uses !== null && $this->current_uses >= $this->max_uses) return 'exhausted';
        return 'active';
    }

    public function uiStatusLabel(): string
    {
        return match ($this->uiStatus()) {
            'active'    => 'Aktif',
            'expired'   => 'Süresi Doldu',
            'exhausted' => 'Tükendi',
            'inactive'  => 'Devre Dışı',
            default     => '—',
        };
    }
}
