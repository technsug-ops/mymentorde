<?php

namespace App\Models\Hr;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class HrLeaveRequest extends Model
{
    protected $table = 'hr_leave_requests';

    protected $fillable = [
        'company_id', 'user_id', 'leave_type', 'start_date', 'end_date',
        'days_count', 'status', 'reason', 'approved_by', 'approved_at', 'rejection_note', 'deputy_user_id',
    ];

    protected $casts = [
        'start_date'  => 'date',
        'end_date'    => 'date',
        'approved_at' => 'datetime',
    ];

    public static array $typeLabels = [
        'annual'    => 'Yıllık İzin',
        'sick'      => 'Hastalık',
        'personal'  => 'Mazeret',
        'maternity' => 'Doğum/Ebeveyn',
        'unpaid'    => 'Ücretsiz İzin',
    ];

    public static array $statusLabels = [
        'pending'   => 'Bekliyor',
        'approved'  => 'Onaylandı',
        'rejected'  => 'Reddedildi',
        'cancelled' => 'İptal',
    ];

    public static array $statusBadge = [
        'pending'   => 'warn',
        'approved'  => 'ok',
        'rejected'  => 'danger',
        'cancelled' => '',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function attachments()
    {
        return $this->hasMany(HrLeaveAttachment::class, 'leave_request_id');
    }

    public function deputy()
    {
        return $this->belongsTo(User::class, 'deputy_user_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }
}
