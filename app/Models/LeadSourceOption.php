<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadSourceOption extends Model
{
    protected $fillable = [
        'code',
        'label',
        'sort_order',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];
}

