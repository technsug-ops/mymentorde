<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManagerReport extends Model
{
    protected $fillable = [
        'company_id',
        'report_type',
        'period_start',
        'period_end',
        'senior_email',
        'sent_to',
        'send_status',
        'sent_at',
        'stats',
        'funnel',
        'trend',
        'created_by',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'sent_to' => 'array',
        'sent_at' => 'datetime',
        'stats' => 'array',
        'funnel' => 'array',
        'trend' => 'array',
    ];
}
