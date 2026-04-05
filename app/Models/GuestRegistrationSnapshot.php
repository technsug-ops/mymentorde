<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuestRegistrationSnapshot extends Model
{
    protected $fillable = [
        'guest_application_id',
        'snapshot_version',
        'submitted_by_email',
        'payload_json',
        'meta_json',
        'submitted_at',
    ];

    protected $casts = [
        'guest_application_id' => 'integer',
        'snapshot_version' => 'integer',
        'payload_json' => 'array',
        'meta_json' => 'array',
        'submitted_at' => 'datetime',
    ];

    public function guestApplication(): BelongsTo
    {
        return $this->belongsTo(GuestApplication::class, 'guest_application_id');
    }
}

