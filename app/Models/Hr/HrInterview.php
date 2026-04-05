<?php

namespace App\Models\Hr;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class HrInterview extends Model
{
    protected $table = 'hr_interviews';

    protected $fillable = [
        'candidate_id', 'interviewer_user_id', 'scheduled_at',
        'duration_minutes', 'type', 'status',
        'score', 'feedback', 'recommendation',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    public static array $typeLabels = [
        'phone'     => 'Telefon',
        'video'     => 'Video (Online)',
        'onsite'    => 'Yüz Yüze',
        'technical' => 'Teknik',
    ];

    public static array $statusLabels = [
        'scheduled'  => 'Planlandı',
        'completed'  => 'Tamamlandı',
        'cancelled'  => 'İptal Edildi',
        'no_show'    => 'Katılmadı',
    ];

    public static array $recommendationLabels = [
        'hire'   => '✅ İşe Al',
        'reject' => '❌ Reddet',
        'maybe'  => '🤔 Belirsiz',
    ];

    public function candidate()
    {
        return $this->belongsTo(HrCandidate::class, 'candidate_id');
    }

    public function interviewer()
    {
        return $this->belongsTo(User::class, 'interviewer_user_id');
    }
}
