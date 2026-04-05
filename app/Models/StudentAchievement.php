<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentAchievement extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'student_id',
        'achievement_code',
        'earned_at',
    ];

    protected $casts = [
        'earned_at' => 'datetime',
    ];
}
