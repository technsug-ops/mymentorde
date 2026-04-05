<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RevenueMilestone extends Model
{
    protected $fillable = [
        'external_id',
        'name_tr',
        'name_de',
        'name_en',
        'description_tr',
        'description_de',
        'description_en',
        'trigger_type',
        'trigger_condition',
        'revenue_type',
        'percentage',
        'fixed_amount',
        'fixed_currency',
        'sort_order',
        'is_active',
        'is_required',
        'created_by',
    ];

    protected $casts = [
        'trigger_condition' => 'array',
        'is_active' => 'boolean',
        'is_required' => 'boolean',
    ];
}
