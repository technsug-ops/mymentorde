<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Model;

class MarketingBudget extends Model
{
    protected $table = 'marketing_budget';

    protected $fillable = [
        'period', 'total_budget', 'currency', 'allocations', 'total_spent', 'total_remaining', 'approved_by',
    ];

    protected $casts = [
        'allocations' => 'array',
    ];
}
