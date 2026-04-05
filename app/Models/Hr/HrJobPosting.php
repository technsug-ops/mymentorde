<?php

namespace App\Models\Hr;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HrJobPosting extends Model
{
    use SoftDeletes;

    protected $table = 'hr_job_postings';

    protected $fillable = [
        'company_id', 'title', 'department', 'role_type', 'employment_type',
        'description', 'requirements', 'is_remote', 'location',
        'salary_min', 'salary_max', 'currency', 'status',
        'deadline_at', 'published_at', 'created_by',
    ];

    protected $casts = [
        'is_remote'    => 'boolean',
        'deadline_at'  => 'date',
        'published_at' => 'datetime',
        'salary_min'   => 'float',
        'salary_max'   => 'float',
    ];

    public static array $statusLabels = [
        'draft'  => 'Taslak',
        'active' => 'Aktif',
        'paused' => 'Durduruldu',
        'closed' => 'Kapatıldı',
    ];

    public static array $statusBadge = [
        'draft'  => 'pending',
        'active' => 'ok',
        'paused' => 'warn',
        'closed' => 'info',
    ];

    public static array $employmentLabels = [
        'full_time'  => 'Tam Zamanlı',
        'part_time'  => 'Yarı Zamanlı',
        'internship' => 'Staj',
        'freelance'  => 'Serbest',
    ];

    public function candidates()
    {
        return $this->hasMany(HrCandidate::class, 'job_posting_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
