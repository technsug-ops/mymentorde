<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentMaterialRead extends Model
{
    protected $fillable = [
        'company_id',
        'student_id',
        'knowledge_base_article_id',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];
}

