<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class UserAwayPeriod extends Model
{
    protected $fillable = [
        'user_id', 'away_from', 'away_until',
        'away_message', 'auto_reply_enabled', 'auto_reply_message', 'timezone',
    ];

    protected $casts = [
        'away_from'           => 'datetime',
        'away_until'          => 'datetime',
        'auto_reply_enabled'  => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        $now = now();
        return $now->between($this->away_from, $this->away_until);
    }

    public function scopeActive($query)
    {
        return $query->where('away_from', '<=', now())
                     ->where('away_until', '>=', now());
    }

    public function scopeUpcoming($query)
    {
        return $query->where('away_from', '>', now())->orderBy('away_from');
    }
}
