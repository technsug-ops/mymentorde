<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadTouchpoint extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'guest_application_id', 'touchpoint_type', 'channel',
        'campaign_id', 'utm_source', 'utm_medium', 'utm_campaign',
        'utm_content', 'utm_term', 'referrer_url', 'landing_page',
        'device_type', 'is_converting_touch', 'touched_at',
    ];

    protected $casts = [
        'is_converting_touch' => 'boolean',
        'touched_at'          => 'datetime',
    ];

    public function guestApplication(): BelongsTo
    {
        return $this->belongsTo(GuestApplication::class);
    }

    public function scopeForGuest($query, $guestId)
    {
        return $query->where('guest_application_id', $guestId)->orderBy('touched_at');
    }
}
