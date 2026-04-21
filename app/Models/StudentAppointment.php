<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
        'cancel_category',
        'google_event_id',
        'google_synced_at',
    ];

    protected $casts = [
        'requested_at'      => 'datetime',
        'scheduled_at'      => 'datetime',
        'cancelled_at'      => 'datetime',
        'google_synced_at'  => 'datetime',
    ];

    public const CANCEL_CATEGORY_LABELS = [
        'student_no_show'    => 'Öğrenci gelmedi',
        'student_request'    => 'Öğrenci iptal istedi',
        'reschedule'         => 'Yeni tarihe ertelendi',
        'senior_unavailable' => 'Danışman müsait değil',
        'duplicate'          => 'Yanlışlıkla açıldı / mükerrer',
        'not_needed'         => 'Artık gerek kalmadı',
        'technical'          => 'Teknik sorun',
        'other'              => 'Diğer',
    ];

    /** Booking modülü: public widget'tan geldiyse karşı taraf (invitee meta). */
    public function publicBooking(): HasOne
    {
        return $this->hasOne(PublicBooking::class, 'student_appointment_id');
    }
}

