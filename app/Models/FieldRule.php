<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FieldRule extends Model
{
    protected $fillable = [
        'name_tr','name_de','name_en','target_field','target_form','condition','exceptions','severity',
        'warning_message_tr','warning_message_de','warning_message_en',
        'block_message_tr','block_message_de','block_message_en',
        'notify_roles','requires_approval','approval_roles','applicable_student_types',
        'is_active','priority','created_by','rule_key',
    ];

    protected $casts = [
        'condition' => 'array',
        'exceptions' => 'array',
        'notify_roles' => 'array',
        'approval_roles' => 'array',
        'applicable_student_types' => 'array',
        'requires_approval' => 'boolean',
        'is_active' => 'boolean',
    ];
}
