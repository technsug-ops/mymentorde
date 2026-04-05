<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentAppointment extends Model
{
    protected $fillable = [
        'company_id',
        'student_id',
        'student_email',
        'senior_email',
        'title',
        'note',
        'requested_at',
        'scheduled_at',
        'duration_minutes',
        'channel',
        'meeting_url',
        'external_event_id',
        'calendar_provider',
        'status',
        'cancelled_at',
        'cancel_reason',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];
}

