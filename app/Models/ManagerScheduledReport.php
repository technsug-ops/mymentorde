<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManagerScheduledReport extends Model
{
    protected $fillable = [
        'company_id',
        'report_type',
        'frequency',
        'day_of_week',
        'day_of_month',
        'send_to',
        'senior_filter',
        'is_active',
        'last_sent_at',
        'created_by',
    ];

    protected $casts = [
        'send_to'      => 'array',
        'is_active'    => 'boolean',
        'last_sent_at' => 'datetime',
    ];

    public const REPORT_TYPE_LABELS = [
        'weekly_summary'    => 'Haftalık Özet',
        'monthly_summary'   => 'Aylık Özet',
        'senior_performance'=> 'Senior Performans',
    ];

    public const FREQUENCY_LABELS = [
        'weekly'  => 'Haftalık',
        'monthly' => 'Aylık',
    ];
}
