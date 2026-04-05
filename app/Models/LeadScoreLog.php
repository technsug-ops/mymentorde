<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadScoreLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'guest_application_id', 'action_code', 'points',
        'score_before', 'score_after', 'tier_before', 'tier_after',
        'metadata', 'created_at',
    ];

    protected $casts = [
        'metadata'   => 'array',
        'created_at' => 'datetime',
    ];

    public function guestApplication(): BelongsTo
    {
        return $this->belongsTo(GuestApplication::class);
    }
}
