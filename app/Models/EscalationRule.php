<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EscalationRule extends Model
{
    protected $fillable = [
        'name',
        'entity_type',
        'duration_hours',
        'escalation_steps',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'escalation_steps' => 'array',
        'is_active' => 'boolean',
    ];
}

