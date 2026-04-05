<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class MarketingTrackingClick extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'tracking_link_id',
        'tracking_code',
        'ip_address',
        'user_agent',
        'referrer_url',
        'landing_url',
        'query_params',
    ];

    protected $casts = [
        'tracking_link_id' => 'integer',
        'query_params' => 'array',
    ];

    public function trackingLink()
    {
        return $this->belongsTo(MarketingTrackingLink::class, 'tracking_link_id');
    }
}
