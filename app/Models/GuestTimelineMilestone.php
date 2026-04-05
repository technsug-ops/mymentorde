<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuestTimelineMilestone extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'guest_application_id',
        'milestone_code',
        'label',
        'category',
        'target_date',
        'completed_at',
        'sort_order',
        'created_at',
    ];

    protected $casts = [
        'target_date'  => 'date',
        'completed_at' => 'datetime',
        'created_at'   => 'datetime',
    ];

    public function guestApplication(): BelongsTo
    {
        return $this->belongsTo(GuestApplication::class);
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    public function isOverdue(): bool
    {
        return !$this->isCompleted() && $this->target_date->isPast();
    }

    public function scopeForGuest($query, int $guestId)
    {
        return $query->where('guest_application_id', $guestId)->orderBy('sort_order');
    }
}
