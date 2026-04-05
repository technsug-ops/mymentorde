<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DealerPayoutRequest extends Model
{
    protected $fillable = [
        'dealer_code',
        'payout_account_id',
        'amount',
        'currency',
        'status',
        'requested_by_email',
        'approved_by',
        'approved_at',
        'paid_at',
        'rejection_reason',
        'receipt_url',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function account()
    {
        return $this->belongsTo(DealerPayoutAccount::class, 'payout_account_id');
    }
}
