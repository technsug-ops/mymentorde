<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InternalNote extends Model
{
    protected $fillable = [
        'student_id',
        'content',
        'category',
        'priority',
        'is_pinned',
        'attachments',
        'created_by',
        'created_by_role',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'attachments' => 'array',
    ];
}
