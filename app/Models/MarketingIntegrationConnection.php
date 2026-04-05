<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class MarketingIntegrationConnection extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'provider',
        'auth_mode',
        'is_enabled',
        'status',
        'account_ref',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'meta',
        'last_checked_at',
        'last_synced_at',
        'last_error',
        'created_by_user_id',
        'updated_by_user_id',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'token_expires_at' => 'datetime',
        'meta' => 'array',
        'last_checked_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
    ];
}

