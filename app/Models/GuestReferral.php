<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuestReferral extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'referrer_guest_id', 'referral_code', 'referred_guest_id',
        'status', 'reward_type', 'reward_applied_at',
    ];

    protected $casts = [
        'reward_applied_at' => 'datetime',
        'created_at'        => 'datetime',
    ];
}
