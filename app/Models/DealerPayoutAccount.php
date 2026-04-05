<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DealerPayoutAccount extends Model
{
    protected $fillable = [
        'dealer_code',
        'bank_name',
        'iban',
        'account_holder',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];
}
