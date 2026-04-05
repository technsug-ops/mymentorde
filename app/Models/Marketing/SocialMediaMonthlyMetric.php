<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Model;

class SocialMediaMonthlyMetric extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'period', 'account_id', 'platform', 'followers_start', 'followers_end',
        'followers_growth', 'followers_growth_rate', 'total_posts', 'total_views',
        'total_likes', 'total_comments', 'total_shares', 'avg_engagement_rate',
        'total_click_through', 'total_guest_registrations', 'top_post_id',
        'top_post_metric', 'calculated_at', 'created_at',
    ];

    protected $casts = [
        'calculated_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function account()
    {
        return $this->belongsTo(SocialMediaAccount::class, 'account_id');
    }

    public function topPost()
    {
        return $this->belongsTo(SocialMediaPost::class, 'top_post_id');
    }
}
