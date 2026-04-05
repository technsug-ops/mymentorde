<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class GuestTicket extends Model
{
    use BelongsToCompany, SoftDeletes;

    protected $fillable = [
        'company_id',
        'guest_application_id',
        'subject',
        'message',
        'status',
        'priority',
        'department',
        'assigned_user_id',
        'created_by_email',
        'last_replied_at',
        'first_response_at',
        'closed_at',
        'routed_at',
        'sla_due_at',
        'sla_hours',
        'attachment_path',
        'attachment_name',
    ];

    protected $casts = [
        'last_replied_at' => 'datetime',
        'first_response_at' => 'datetime',
        'closed_at' => 'datetime',
        'routed_at' => 'datetime',
        'sla_due_at' => 'datetime',
    ];

    public function guestApplication()
    {
        return $this->belongsTo(GuestApplication::class, 'guest_application_id');
    }

    public function replies()
    {
        return $this->hasMany(GuestTicketReply::class, 'guest_ticket_id');
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }
}
