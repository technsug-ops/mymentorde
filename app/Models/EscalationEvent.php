<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EscalationEvent extends Model
{
    protected $fillable = [
        'escalation_rule_id',
        'entity_type',
        'entity_id',
        'step_no',
        'action',
        'targets',
        'channels',
        'triggered_at',
        'status',
    ];

    protected $casts = [
        'targets' => 'array',
        'channels' => 'array',
        'triggered_at' => 'datetime',
    ];
}

