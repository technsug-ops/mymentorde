<?php

namespace App\Models\Hr;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class HrAttendance extends Model
{
    protected $fillable = [
        'user_id',
        'company_id',
        'work_date',
        'check_in_at',
        'check_out_at',
        'work_minutes',
        'status',
        'note',
    ];

    protected $casts = [
        'work_date'    => 'date',
        'check_in_at'  => 'datetime',
        'check_out_at' => 'datetime',
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function calculateWorkMinutes(): int
    {
        if (!$this->check_in_at || !$this->check_out_at) {
            return 0;
        }
        return (int) $this->check_in_at->diffInMinutes($this->check_out_at);
    }
}
