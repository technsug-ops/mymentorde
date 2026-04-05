<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Model;

class EventRegistration extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'event_id', 'user_id', 'first_name', 'last_name', 'email', 'phone', 'role', 'mentorde_id',
        'status', 'attended_at', 'cancelled_at', 'cancellation_reason',
        'survey_completed', 'survey_score', 'survey_feedback', 'converted_to_guest_after',
        'converted_guest_id', 'source', 'registered_at', 'created_at',
    ];

    protected $casts = [
        'survey_completed' => 'boolean',
        'converted_to_guest_after' => 'boolean',
        'attended_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'registered_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function event()
    {
        return $this->belongsTo(MarketingEvent::class, 'event_id');
    }

    public function scopeAttended($q)
    {
        return $q->where('status', 'attended');
    }

    public function scopeNoShow($q)
    {
        return $q->where('status', 'no_show');
    }

    public function fullName(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
