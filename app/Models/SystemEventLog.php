<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class SystemEventLog extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'event_type',
        'entity_type',
        'entity_id',
        'message',
        'meta',
        'actor_email',
    ];

    protected $casts = [
        'meta' => 'array',
    ];
}

