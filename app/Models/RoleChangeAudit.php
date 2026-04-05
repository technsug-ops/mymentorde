<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoleChangeAudit extends Model
{
    protected $fillable = [
        'actor_user_id',
        'action',
        'target_type',
        'target_id',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];
}
