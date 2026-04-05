<?php

namespace App\Models\Hr;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class HrPersonProfile extends Model
{
    protected $table = 'hr_person_profiles';

    protected $fillable = [
        'company_id', 'user_id', 'hire_date', 'position_title',
        'phone', 'emergency_contact_name', 'emergency_contact_phone',
        'annual_leave_quota', 'notes',
    ];

    protected $casts = [
        'hire_date'          => 'date',
        'annual_leave_quota' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function usedLeaveDays(int $year): int
    {
        return HrLeaveRequest::where('user_id', $this->user_id)
            ->where('leave_type', 'annual')
            ->where('status', 'approved')
            ->whereYear('start_date', $year)
            ->sum('days_count');
    }

    public function remainingLeaveDays(int $year): int
    {
        return max(0, $this->annual_leave_quota - $this->usedLeaveDays($year));
    }
}
