<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Model;

class SocialMediaAccount extends Model
{
    protected $fillable = [
        'platform', 'account_name', 'account_url', 'profile_image_url',
        'followers', 'followers_growth_this_month', 'total_posts', 'metrics_last_updated_at',
        'api_connected', 'api_access_token', 'api_token_expires_at', 'is_active',
        'external_account_id', 'last_synced_at',
    ];

    protected $casts = [
        'api_connected' => 'boolean',
        'is_active' => 'boolean',
        'metrics_last_updated_at' => 'datetime',
        'api_token_expires_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    protected $hidden = ['api_access_token'];

    public function posts()
    {
        return $this->hasMany(SocialMediaPost::class, 'account_id');
    }

    public function monthlyMetrics()
    {
        return $this->hasMany(SocialMediaMonthlyMetric::class, 'account_id');
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }
}
