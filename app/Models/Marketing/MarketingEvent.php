<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Model;

class MarketingEvent extends Model
{
    protected $fillable = [
        'title_tr', 'title_de', 'title_en', 'description_tr', 'description_de', 'description_en',
        'start_date', 'end_date', 'timezone', 'type', 'format',
        'online_platform', 'online_meeting_url', 'online_meeting_id', 'online_meeting_password', 'online_recording_url',
        'venue_name', 'venue_address', 'venue_city', 'venue_country', 'venue_map_url',
        'capacity', 'current_registrations', 'waitlist_enabled',
        'target_audience', 'target_student_types', 'cover_image_url', 'gallery_urls',
        'linked_campaign_id', 'cms_content_id', 'reminders',
        'post_event_survey_enabled', 'post_event_survey_url',
        'status', 'created_by',
    ];

    protected $casts = [
        'target_student_types' => 'array',
        'gallery_urls' => 'array',
        'reminders' => 'array',
        'waitlist_enabled' => 'boolean',
        'post_event_survey_enabled' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function registrations()
    {
        return $this->hasMany(EventRegistration::class, 'event_id');
    }

    public function cmsContent()
    {
        return $this->belongsTo(CmsContent::class, 'cms_content_id');
    }

    public function scopeUpcoming($q)
    {
        return $q->where('start_date', '>', now())->orderBy('start_date');
    }

    public function scopePast($q)
    {
        return $q->where('start_date', '<', now())->orderByDesc('start_date');
    }

    public function title(string $l = 'tr')
    {
        return $this->{"title_{$l}"} ?? $this->title_tr;
    }

    public function isFull(): bool
    {
        return $this->capacity && $this->current_registrations >= $this->capacity;
    }
}
