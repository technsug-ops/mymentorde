<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DealerStudentRevenue extends Model
{
    protected $fillable = [
        'dealer_id',
        'student_id',
        'dealer_type',
        'milestone_progress',
        'total_earned',
        'total_pending',
    ];

    protected $casts = [
        'milestone_progress' => 'array',
    ];
}
