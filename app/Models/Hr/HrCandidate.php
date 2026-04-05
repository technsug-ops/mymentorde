<?php

namespace App\Models\Hr;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HrCandidate extends Model
{
    use SoftDeletes;

    protected $table = 'hr_candidates';

    protected $fillable = [
        'company_id', 'job_posting_id', 'first_name', 'last_name',
        'email', 'phone', 'cv_path', 'cover_letter_path',
        'portfolio_url', 'linkedin_url', 'source', 'status',
        'rating', 'notes', 'assigned_to', 'rejection_reason',
    ];

    public static array $statusLabels = [
        'applied'   => 'Başvurdu',
        'screening' => 'Ön Değerlendirme',
        'interview' => 'Mülakat',
        'offer'     => 'Teklif Verildi',
        'hired'     => 'İşe Alındı',
        'rejected'  => 'Reddedildi',
    ];

    public static array $statusBadge = [
        'applied'   => 'info',
        'screening' => 'pending',
        'interview' => 'warn',
        'offer'     => 'ok',
        'hired'     => 'ok',
        'rejected'  => 'danger',
    ];

    public static array $sourceLabels = [
        'linkedin'  => 'LinkedIn',
        'referral'  => 'Referans',
        'website'   => 'Web Sitesi',
        'agency'    => 'Ajans',
        'direct'    => 'Direkt Başvuru',
    ];

    public static array $statusOrder = [
        'applied' => 1, 'screening' => 2, 'interview' => 3,
        'offer' => 4, 'hired' => 5, 'rejected' => 6,
    ];

    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function posting()
    {
        return $this->belongsTo(HrJobPosting::class, 'job_posting_id');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function interviews()
    {
        return $this->hasMany(HrInterview::class, 'candidate_id')->orderByDesc('scheduled_at');
    }
}
