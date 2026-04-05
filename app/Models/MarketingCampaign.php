<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class MarketingCampaign extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'channel',
        'channels',
        'budget',
        'spent_amount',
        'currency',
        'start_date',
        'end_date',
        'target_audience',
        'target_country',
        'status',
        'utm_params',
        'metrics',
        'linked_cms_content_ids',
        'image_url',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'channels' => 'array',
        'utm_params' => 'array',
        'metrics' => 'array',
        'linked_cms_content_ids' => 'array',
    ];
}
