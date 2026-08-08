<?php

namespace App\Models;

use App\Models\Concerns\OwnedBySubjectCompany;
use App\Models\Contracts\ResolvesOwnCompany;
use Illuminate\Database\Eloquent\Model;

class DmThread extends Model implements ResolvesOwnCompany
{
    use OwnedBySubjectCompany;

    protected $table = 'dm_threads';

    protected $fillable = [
        'company_id',
        'thread_type',
        'guest_application_id',
        'student_id',
        'advisor_user_id',
        'initiated_by_user_id',
        'status',
        'department',
        'sla_hours',
        'next_response_due_at',
        'last_participant_message_at',
        'last_advisor_reply_at',
        'last_message_preview',
        'last_message_at',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'guest_application_id' => 'integer',
        'advisor_user_id' => 'integer',
        'initiated_by_user_id' => 'integer',
        'sla_hours' => 'integer',
        'next_response_due_at' => 'datetime',
        'last_participant_message_at' => 'datetime',
        'last_advisor_reply_at' => 'datetime',
        'last_message_at' => 'datetime',
    ];

    public function messages()
    {
        return $this->hasMany(DmMessage::class, 'thread_id');
    }
}
