<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeniorResponseTemplate extends Model
{
    protected $table = 'senior_response_templates';

    protected $fillable = [
        'company_id',
        'owner_user_id',
        'category',
        'title',
        'body',
        'usage_count',
        'is_active',
    ];

    protected $casts = [
        'is_active'   => 'boolean',
        'usage_count' => 'integer',
    ];
}
