<?php

namespace App\Models\Marketing;

use App\Models\GuestApplication;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailDripEnrollment extends Model
{
    public $timestamps = false;

    protected $table = 'email_drip_enrollments';

    protected $fillable = [
        'drip_sequence_id',
        'guest_application_id',
        'current_step',
        'status',
        'next_send_at',
        'enrolled_at',
        'completed_at',
    ];

    protected $casts = [
        'current_step' => 'integer',
        'next_send_at' => 'datetime',
        'enrolled_at'  => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function sequence(): BelongsTo
    {
        return $this->belongsTo(EmailDripSequence::class, 'drip_sequence_id');
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(GuestApplication::class, 'guest_application_id');
    }
}
