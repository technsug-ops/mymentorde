<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class MarketingExternalMetric extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'row_hash',
        'provider',
        'account_ref',
        'metric_date',
        'campaign_key',
        'campaign_name',
        'source',
        'medium',
        'impressions',
        'clicks',
        'spend',
        'leads',
        'conversions',
        'raw_payload',
        'synced_at',
    ];

    protected $casts = [
        'metric_date' => 'date',
        'impressions' => 'integer',
        'clicks' => 'integer',
        'spend' => 'float',
        'leads' => 'integer',
        'conversions' => 'integer',
        'raw_payload' => 'array',
        'synced_at' => 'datetime',
    ];
}
