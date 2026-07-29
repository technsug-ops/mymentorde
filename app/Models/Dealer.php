<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dealer extends Model
{
    use BelongsToCompany, SoftDeletes;
    protected $fillable = [
        'company_id',
        'parent_dealer_id',
        'code',
        'internal_sequence',
        'name',
        'email',
        'phone',
        'whatsapp',
        'dealer_type_code',
        'roles',
        'is_active',
        'is_archived',
        'archived_by',
        'archived_at',
        'signup_bonus_amount',
        'signup_bonus_status',
        'signup_bonus_unlocked_at',
        'override_rate_eur',
        'override_rate_percent',
        'override_basis',
        'public_slug',
        'site_enabled',
        'site_logo_path',
        'site_accent_color',
        'site_hero_title',
        'site_hero_subtitle',
        'site_hero_image_path',
        'site_about_text',
        'site_phone',
        'site_whatsapp',
        'site_instagram',
        'site_services',
        'site_stats',
        'site_team',
        'site_testimonials',
        'site_packages',
        'site_package_note',
        'site_faq',
        'site_universities',
        'site_sections',
        'site_address',
        'site_show_badge',
        'site_template',
        'custom_domain',
        'custom_domain_verified_at',
        'custom_domain_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_archived' => 'boolean',
        'archived_at' => 'datetime',
        'roles' => 'array',
        'signup_bonus_amount' => 'decimal:2',
        'signup_bonus_unlocked_at' => 'datetime',
        'override_rate_eur' => 'decimal:2',
        'override_rate_percent' => 'decimal:2',
        'site_enabled' => 'boolean',
        'site_services' => 'array',
        'site_stats' => 'array',
        'site_team' => 'array',
        'site_testimonials' => 'array',
        'site_packages' => 'array',
        'site_faq' => 'array',
        'site_universities' => 'array',
        'site_sections' => 'array',
        'site_show_badge' => 'boolean',
        'custom_domain_verified_at' => 'datetime',
    ];

    // ── Çalışma rolleri (kapasite seti) ───────────────────────────────────────
    // Bir bayi birden çok rolde çalışabilir. dealer_type_code primary tier'dır;
    // roles sadece hangi iş modellerinde çalıştığını belirtir.
    public const ROLE_LEAD_GENERATION = 'lead_generation';
    public const ROLE_FREELANCE        = 'freelance';
    public const ROLE_B2B_PARTNER      = 'b2b_partner';

    /** UI'da açılıp kapatılabilen roller (b2b ayrı bir tier, toggle dışı). */
    public const TOGGLEABLE_ROLES = [self::ROLE_LEAD_GENERATION, self::ROLE_FREELANCE];

    public const ROLE_LABELS = [
        self::ROLE_LEAD_GENERATION => 'Lead Generation (Referral)',
        self::ROLE_FREELANCE        => 'Freelance Danışman',
        self::ROLE_B2B_PARTNER      => 'B2B Partner',
    ];

    public const ROLE_TO_TYPE = [
        self::ROLE_LEAD_GENERATION => 'lead_generation',
        self::ROLE_FREELANCE        => 'freelance_danisman',
        self::ROLE_B2B_PARTNER      => 'b2b_partner',
    ];

    /** Etkin roller — kayıt boşsa primary type'tan türet (geriye dönük uyum). */
    public function rolesList(): array
    {
        $roles = is_array($this->roles) ? array_values(array_filter($this->roles)) : [];
        if (!empty($roles)) {
            return $roles;
        }
        return array_keys(array_filter(self::ROLE_TO_TYPE, fn ($t) => $t === $this->dealer_type_code));
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->rolesList(), true);
    }

    public function roleLabels(): array
    {
        return array_map(fn ($r) => self::ROLE_LABELS[$r] ?? $r, $this->rolesList());
    }

    /**
     * Rol setinden primary dealer_type_code seç: en yetkili (izin superset)
     * tier kazanır — freelance > lead_generation. Boşsa lead_generation.
     */
    public static function primaryTypeForRoles(array $roles): string
    {
        if (in_array(self::ROLE_B2B_PARTNER, $roles, true))      return 'b2b_partner';
        if (in_array(self::ROLE_FREELANCE, $roles, true))        return 'freelance_danisman';
        if (in_array(self::ROLE_LEAD_GENERATION, $roles, true))  return 'lead_generation';
        return 'lead_generation';
    }

    // ── Hiyerarşi (2 seviye: bölge → alt bayi) ────────────────

    /** Üst bayi (bölge). Alt bayide dolu, bölge bayisinde null. */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_dealer_id');
    }

    /** Bu bölge bayisinin alt bayileri. */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_dealer_id');
    }

    /** parent_dealer_id null => bölge bayisi (kendi alt bayisi olabilir). */
    public function isRegional(): bool
    {
        return $this->parent_dealer_id === null;
    }

    /** parent_dealer_id dolu => alt bayi. */
    public function isSub(): bool
    {
        return $this->parent_dealer_id !== null;
    }

    /**
     * Görünürlük roll-up için bu bayinin kapsadığı dealer CODE'ları.
     * Bölge bayisi: kendi + tüm alt bayileri. Alt bayi: sadece kendi.
     * DealerStudentRevenue.dealer_id ve guest_applications.dealer_code CODE tutar.
     */
    public function scopeCodes(): array
    {
        if ($this->isRegional()) {
            $childCodes = $this->children()->pluck('code')->all();
            return array_values(array_filter(array_merge([$this->code], $childCodes)));
        }
        return [$this->code];
    }

    // ── Bonus helpers ─────────────────────────────────────────

    public function isBonusLocked(): bool
    {
        return ($this->signup_bonus_status ?? 'locked') === 'locked';
    }

    public function isBonusPending(): bool
    {
        return ($this->signup_bonus_status ?? 'locked') === 'pending';
    }

    public function isBonusUnlocked(): bool
    {
        return ($this->signup_bonus_status ?? 'locked') === 'unlocked';
    }

    /**
     * İlk lead yönlendirildiğinde: locked → pending
     */
    public function advanceBonusToPending(): void
    {
        if ($this->isBonusLocked()) {
            $this->forceFill(['signup_bonus_status' => 'pending'])->save();
        }
    }

    /**
     * Lead dönüşüp ilk ödeme alındığında: pending → unlocked
     */
    public function unlockBonus(): void
    {
        if (!$this->isBonusUnlocked()) {
            $this->forceFill([
                'signup_bonus_status' => 'unlocked',
                'signup_bonus_unlocked_at' => now(),
            ])->save();
        }
    }
}
