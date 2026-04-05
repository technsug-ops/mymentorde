<?php

namespace App\Models\Marketing;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class SocialMediaPost extends Model
{
    protected $fillable = [
        'account_id', 'platform', 'caption', 'media_urls', 'post_type', 'post_url',
        'status', 'scheduled_at', 'published_at', 'metric_views', 'metric_likes',
        'metric_comments', 'metric_shares', 'metric_saves', 'metric_reach',
        'metric_impressions', 'metric_engagement_rate', 'metric_click_through',
        'metric_guest_registrations', 'tags', 'linked_campaign_id', 'linked_content_id',
        'assigned_to', 'created_by',
    ];

    protected $casts = [
        'media_urls' => 'array',
        'tags' => 'array',
        'scheduled_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public function account()
    {
        return $this->belongsTo(SocialMediaAccount::class, 'account_id');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function content()
    {
        return $this->belongsTo(CmsContent::class, 'linked_content_id');
    }

    public function scopePublished($q)
    {
        return $q->where('status', 'published');
    }

    public function scopeScheduled($q)
    {
        return $q->where('status', 'scheduled');
    }

    public function scopeCalendar($q, string $month)
    {
        return $q->whereRaw("DATE_FORMAT(COALESCE(published_at, scheduled_at), '%Y-%m') = ?", [$month]);
    }

    public function totalEngagement(): int
    {
        return $this->metric_likes + $this->metric_comments + $this->metric_shares + $this->metric_saves;
    }
}
