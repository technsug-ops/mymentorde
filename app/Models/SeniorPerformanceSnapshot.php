<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class SeniorPerformanceSnapshot extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'senior_email',
        'period',
        'student_count',
        'active_count',
        'converted_count',
        'university_accepted_count',
        'university_rejected_count',
        'visa_approved_count',
        'avg_process_days',
        'revenue_generated',
        'snapshotted_at',
    ];

    protected $casts = [
        'student_count' => 'integer',
        'active_count' => 'integer',
        'converted_count' => 'integer',
        'university_accepted_count' => 'integer',
        'university_rejected_count' => 'integer',
        'visa_approved_count' => 'integer',
        'avg_process_days' => 'float',
        'revenue_generated' => 'decimal:2',
        'snapshotted_at' => 'datetime',
    ];

    /** Kabul oranı: kabul / (kabul + ret) */
    public function acceptanceRate(): float
    {
        $total = $this->university_accepted_count + $this->university_rejected_count;
        return $total > 0 ? round($this->university_accepted_count / $total * 100, 1) : 0.0;
    }
}
