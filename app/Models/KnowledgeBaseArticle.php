<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KnowledgeBaseArticle extends Model
{
    protected $fillable = [
        'title_tr',
        'title_de',
        'title_en',
        'body_tr',
        'body_de',
        'body_en',
        'source_url',
        'media_type',
        'file_path',
        'original_filename',
        'category',
        'tags',
        'target_roles',
        'is_published',
        'author_id',
        'view_count',
    ];

    protected $casts = [
        'tags' => 'array',
        'target_roles' => 'array',
        'is_published' => 'boolean',
    ];
}

