<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CurrencyRate extends Model
{
    protected $fillable = [
        'base_currency',
        'target_currency',
        'rate',
        'fetched_at',
        'source',
    ];

    protected $casts = [
        'rate'       => 'float',
        'fetched_at' => 'date',
    ];
}
