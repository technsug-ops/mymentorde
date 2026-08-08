<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class WebhookLog extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'source',
        'event_type',
        'status',
        'payload',
        'error_message',
        'retry_count',
        'processed_at',
    ];

    protected $casts = [
        'payload'      => 'array',
        'processed_at' => 'datetime',
    ];
}
