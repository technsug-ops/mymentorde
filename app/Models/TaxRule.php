<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class TaxRule extends Model
{
    use BelongsToCompany;

    protected $table = 'tax_rules';

    protected $fillable = [
        'company_id',
        'rule_name',
        'match_country_code',
        'match_customer_type',
        'tax_rate_pct',
        'tax_code',
        'invoice_note',
        'priority',
        'is_active',
    ];

    protected $casts = [
        'tax_rate_pct' => 'decimal:2',
        'priority'     => 'integer',
        'is_active'    => 'boolean',
    ];
}
