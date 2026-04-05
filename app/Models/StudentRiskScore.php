<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentRiskScore extends Model
{
    protected $fillable = [
        'student_id',
        'current_score',
        'risk_level',
        'factors',
        'last_calculated_at',
        'history',
    ];

    protected $casts = [
        'factors' => 'array',
        'history' => 'array',
        'last_calculated_at' => 'datetime',
    ];

    public function student(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'student_id');
    }
}

