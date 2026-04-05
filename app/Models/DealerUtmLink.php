<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DealerUtmLink extends Model
{
    protected $fillable = [
        'dealer_code',
        'label',
        'utm_campaign',
        'utm_source',
        'utm_medium',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
