<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentRevenue extends Model
{
    protected $fillable = [
        'student_id',
        'package_id',
        'package_total_price',
        'package_currency',
        'milestone_progress',
        'total_earned',
        'total_pending',
        'total_remaining',
    ];

    protected $casts = [
        'milestone_progress' => 'array',
    ];
}
