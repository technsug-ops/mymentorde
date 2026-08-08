<?php

namespace App\Models;

use App\Models\Concerns\OwnedBySubjectCompany;
use App\Models\Contracts\ResolvesOwnCompany;
use Illuminate\Database\Eloquent\Model;

class NotificationDispatch extends Model implements ResolvesOwnCompany
{
    use OwnedBySubjectCompany;

    protected $fillable = [
        'user_id',
        'company_id',
        'template_id',
        'channel',
        'category',
        'student_id',
        'guest_id',
        'recipient_email',
        'recipient_phone',
        'recipient_name',
        'subject',
        'body',
        'variables',
        'status',
        'is_read',
        'read_at',
        'queued_at',
        'sent_at',
        'failed_at',
        'fail_reason',
        'skip_reason',
        'source_type',
        'source_id',
        'triggered_by',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_read'   => 'boolean',
        'read_at'   => 'datetime',
        'queued_at' => 'datetime',
        'sent_at'   => 'datetime',
        'failed_at' => 'datetime',
    ];
}

