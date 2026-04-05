<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeniorPerformanceTarget extends Model
{
    protected $table = 'senior_performance_targets';

    protected $fillable = [
        'company_id',
        'senior_email',
        'period',
        'target_conversions',
        'target_outcomes',
        'target_doc_reviews',
        'target_appointments',
        'set_by_user_id',
    ];

    protected $casts = [
        'target_conversions'  => 'integer',
        'target_outcomes'     => 'integer',
        'target_doc_reviews'  => 'integer',
        'target_appointments' => 'integer',
    ];
}
