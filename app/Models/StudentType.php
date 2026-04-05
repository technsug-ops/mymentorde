<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentType extends Model
{
    protected $fillable = [
        'name_tr',
        'name_de',
        'name_en',
        'code',
        'id_prefix',
        'description_tr',
        'description_de',
        'description_en',
        'applicable_processes',
        'required_document_categories',
        'default_checklist_template_id',
        'field_rules',
        'is_active',
        'sort_order',
        'created_by',
    ];

    protected $casts = [
        'applicable_processes' => 'array',
        'required_document_categories' => 'array',
        'field_rules' => 'array',
        'is_active' => 'boolean',
    ];
}
