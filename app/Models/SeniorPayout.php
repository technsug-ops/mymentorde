<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SeniorPayout extends Model
{
    use BelongsToCompany;

    protected $table = 'senior_payouts';

    protected $fillable = [
        'company_id',
        'senior_user_id',
        'amount_cents',
        'currency',
        'period_start',
        'period_end',
        'status',
        'method',
        'stripe_transfer_id',
        'external_reference',
        'notes',
        'failure_reason',
        'requested_at',
        'paid_at',
    ];

    protected $casts = [
        'amount_cents' => 'integer',
        'period_start' => 'date',
        'period_end'   => 'date',
        'requested_at' => 'datetime',
        'paid_at'      => 'datetime',
    ];

    public function senior(): BelongsTo
    {
        return $this->belongsTo(User::class, 'senior_user_id');
    }

    public function earnings(): HasMany
    {
        return $this->hasMany(SeniorEarning::class, 'payout_id');
    }
}
