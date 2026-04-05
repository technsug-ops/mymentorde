<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManagerPerformanceTarget extends Model
{
    protected $fillable = [
        'company_id',
        'period',
        'target_type',
        'senior_email',
        'target_revenue',
        'target_conversions',
        'target_new_guests',
        'target_doc_reviews',
        'target_contracts_signed',
        'set_by_user_id',
        'notes',
    ];

    protected $casts = [
        'target_revenue'           => 'float',
        'target_conversions'       => 'integer',
        'target_new_guests'        => 'integer',
        'target_doc_reviews'       => 'integer',
        'target_contracts_signed'  => 'integer',
    ];
}
