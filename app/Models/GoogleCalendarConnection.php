<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoogleCalendarConnection extends Model
{
    protected $fillable = [
        'user_id',
        'google_email',
        'google_user_id',
        'access_token',
        'refresh_token',
        'expires_at',
        'scope',
        'calendar_id',
        'calendar_summary',
        'sync_push',
        'sync_pull',
        'last_sync_status',
        'last_sync_error',
        'last_synced_at',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    protected function casts(): array
    {
        return [
            'expires_at'      => 'datetime',
            'last_synced_at'  => 'datetime',
            'sync_push'       => 'boolean',
            'sync_pull'       => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Access token süresi dolmuş mu? */
    public function isAccessTokenExpired(): bool
    {
        if (! $this->expires_at) return true;
        return now()->greaterThanOrEqualTo($this->expires_at->subMinute()); // 1dk buffer
    }
}
