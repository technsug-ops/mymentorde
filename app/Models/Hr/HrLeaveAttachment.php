<?php

namespace App\Models\Hr;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class HrLeaveAttachment extends Model
{
    protected $table = 'hr_leave_attachments';

    protected $fillable = [
        'leave_request_id', 'type', 'original_name', 'path', 'url', 'uploaded_by',
    ];

    public function leaveRequest()
    {
        return $this->belongsTo(HrLeaveRequest::class, 'leave_request_id');
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
