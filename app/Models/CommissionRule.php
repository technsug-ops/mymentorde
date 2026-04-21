<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class CommissionRule extends Model
{
    use BelongsToCompany;

    protected $table = 'commission_rules';

    protected $fillable = [
        'company_id',
        'rule_name',
        'applies_to_tier',
        'applies_to_service_type',
        'commission_pct',
        'priority',
        'is_active',
    ];

    protected $casts = [
        'commission_pct' => 'decimal:2',
        'priority'       => 'integer',
        'is_active'      => 'boolean',
    ];
}
