<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManagerAlertRule extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'condition_type',
        'threshold_value',
        'check_frequency',
        'notify_channels',
        'notify_emails',
        'is_active',
        'last_triggered_at',
        'created_by',
    ];

    protected $casts = [
        'notify_channels'   => 'array',
        'notify_emails'     => 'array',
        'is_active'         => 'boolean',
        'threshold_value'   => 'float',
        'last_triggered_at' => 'datetime',
    ];

    public const CONDITION_LABELS = [
        'risk_score_above'   => 'Risk skoru üstünde',
        'revenue_below'      => 'Gelir altında',
        'inactive_students'  => 'İnaktif öğrenci sayısı',
        'pending_docs_above' => 'Bekleyen belge sayısı',
        'overdue_outcomes'   => 'Süresi geçmiş süreç',
    ];

    public const FREQUENCY_LABELS = [
        'hourly' => 'Saatlik',
        'daily'  => 'Günlük',
        'weekly' => 'Haftalık',
    ];
}
