<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InternalNote extends Model
{
    protected $fillable = [
        'student_id',
        'guest_application_id',
        'content',
        'category',
        'priority',
        'is_pinned',
        'attachments',
        'archived_at',
        'archived_by',
        'created_by',
        'created_by_role',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'attachments' => 'array',
        'archived_at' => 'datetime',
    ];
}
