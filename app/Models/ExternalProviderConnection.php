<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class ExternalProviderConnection extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'provider',
        'account_label',
        'status',
        'oauth_client_id',
        'scopes',
        'last_sync_at',
        'last_error',
        'meta',
    ];

    protected $casts = [
        'last_sync_at' => 'datetime',
        'meta' => 'array',
    ];
}

