<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dealer extends Model
{
    use BelongsToCompany, SoftDeletes;
    protected $fillable = [
        'company_id',
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
