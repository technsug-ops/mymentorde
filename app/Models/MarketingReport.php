<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class MarketingReport extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'report_type',
        'period_start',
        'period_end',
        'filters',
        'kpis',
        'source_summary',
        'pipeline_summary',
        'trend',
        'created_by',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'filters' => 'array',
        'kpis' => 'array',
        'source_summary' => 'array',
        'pipeline_summary' => 'array',
        'trend' => 'array',
    ];
}
