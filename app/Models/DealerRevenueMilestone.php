<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DealerRevenueMilestone extends Model
{
    protected $fillable = [
        'external_id',
        'name_tr',
        'name_de',
        'name_en',
        'trigger_type',
        'trigger_condition',
        'revenue_type',
        'percentage',
        'fixed_amount',
        'fixed_currency',
        'applicable_dealer_types',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'trigger_condition' => 'array',
        'applicable_dealer_types' => 'array',
        'is_active' => 'boolean',
    ];
}
