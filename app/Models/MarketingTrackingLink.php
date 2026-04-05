<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class MarketingTrackingLink extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'title',
        'code',
        'category_code',
        'platform_code',
        'placement_code',
        'variation_no',
        'destination_path',
        'campaign_id',
        'campaign_code',
        'dealer_code',
        'source_code',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'status',
        'click_count',
        'last_clicked_at',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'campaign_id' => 'integer',
        'variation_no' => 'integer',
        'click_count' => 'integer',
        'last_clicked_at' => 'datetime',
    ];

    public function campaign()
    {
        return $this->belongsTo(MarketingCampaign::class, 'campaign_id');
    }
}
