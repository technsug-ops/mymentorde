<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DealerType extends Model
{
    protected $fillable = [
        'name_tr',
        'name_de',
        'name_en',
        'code',
        'description_tr',
        'description_de',
        'description_en',
        'permissions',
        'default_commission_config',
        'is_active',
        'sort_order',
        'created_by',
    ];

    protected $casts = [
        'permissions' => 'array',
        'default_commission_config' => 'array',
        'is_active' => 'boolean',
    ];
}
