<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntegrationConfig extends Model
{
    protected $fillable = [
        'category',
        'active_provider',
        'providers',
        'is_enabled',
        'last_sync_at',
        'status',
        'updated_by',
    ];

    protected $casts = [
        'providers' => 'array',
        'is_enabled' => 'boolean',
        'last_sync_at' => 'datetime',
    ];
}
