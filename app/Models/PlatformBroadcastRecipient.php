<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Platform Owner Broadcast — Alici (recipient).
 *
 * Her broadcast bir veya daha fazla user'a dustur; bu tablo open/click
 * tracking ve gonderim durumu icin tutulur.
 */
class PlatformBroadcastRecipient extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT    = 'sent';
    public const STATUS_FAILED  = 'failed';
    public const STATUS_OPENED  = 'opened';
    public const STATUS_CLICKED = 'clicked';

    protected $fillable = [
        'broadcast_id',
        'company_id',
        'user_id',
        'delivered_at',
        'opened_at',
        'clicked_at',
        'status',
        'error',
    ];

    protected $casts = [
        'delivered_at' => 'datetime',
        'opened_at'    => 'datetime',
        'clicked_at'   => 'datetime',
    ];

    public function broadcast(): BelongsTo
    {
        return $this->belongsTo(PlatformBroadcast::class, 'broadcast_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
