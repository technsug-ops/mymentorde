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
        'is_active',
        'is_archived',
        'archived_by',
        'archived_at',
        'signup_bonus_amount',
        'signup_bonus_status',
        'signup_bonus_unlocked_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_archived' => 'boolean',
        'archived_at' => 'datetime',
        'signup_bonus_amount' => 'decimal:2',
        'signup_bonus_unlocked_at' => 'datetime',
    ];

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
