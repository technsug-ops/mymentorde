<?php

namespace App\Models\Marketing;

use App\Models\MarketingCampaign;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CmsContent extends Model
{
    protected $fillable = [
        'type', 'slug', 'title_tr', 'title_de', 'title_en',
        'summary_tr', 'summary_de', 'summary_en',
        'content_tr', 'content_de', 'content_en',
        'cover_image_url', 'cover_image_alt', 'gallery_urls',
        'video_url', 'video_thumbnail_url',
        'seo_meta_title_tr', 'seo_meta_title_de', 'seo_meta_title_en',
        'seo_meta_description_tr', 'seo_meta_description_de', 'seo_meta_description_en',
        'seo_keywords', 'seo_canonical_url', 'seo_og_image_url',
        'status', 'published_at', 'scheduled_at', 'archived_at',
        'is_featured', 'featured_order',
        'target_audience', 'target_student_types', 'linked_campaign_id',
        'category', 'tags',
        'current_revision', 'created_by', 'last_edited_by', 'approved_by',
    ];

    protected $casts = [
        'gallery_urls' => 'array',
        'seo_keywords' => 'array',
        'target_student_types' => 'array',
        'tags' => 'array',
        'is_featured' => 'boolean',
        'published_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    public function revisions()
    {
        return $this->hasMany(CmsContentRevision::class)->orderByDesc('revision_number');
    }

    public function campaign()
    {
        return $this->belongsTo(MarketingCampaign::class, 'linked_campaign_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('status', 'published');
    }

    public function scopeOfType(Builder $q, string $type): Builder
    {
        return $q->where('type', $type);
    }

    public function scopeFeatured(Builder $q): Builder
    {
        return $q->where('is_featured', true)->orderBy('featured_order');
    }

    public function scopeForAudience(Builder $q, string $audience): Builder
    {
        return $q->whereIn('target_audience', ['all', $audience]);
    }

    public function title(string $lang = 'tr'): string
    {
        return $this->{"title_{$lang}"} ?? $this->title_tr;
    }

    public function incrementViews(bool $unique = false): void
    {
        $this->increment('metric_total_views');
        if ($unique) {
            $this->increment('metric_unique_views');
        }
    }
}
