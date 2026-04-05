<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffKpiTarget extends Model
{
    protected $fillable = [
        'company_id', 'user_id', 'period',
        'target_tasks_done', 'target_tickets_resolved', 'target_hours_logged',
        'set_by_user_id',
    ];

    protected $casts = [
        'target_hours_logged' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
