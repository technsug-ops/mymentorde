<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentOnboardingStep extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'student_id',
        'step_code',
        'completed_at',
        'skipped_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'skipped_at'   => 'datetime',
        'created_at'   => 'datetime',
    ];

    public const STEPS = [
        'welcome',
        'profile',
        'meet_senior',
        'first_docs',
        'select_package',
    ];

    public const STEP_LABELS = [
        'welcome'        => 'Hoş Geldin!',
        'profile'        => 'Profilini Tamamla',
        'meet_senior'    => 'Danışmanını Tanı',
        'first_docs'     => 'İlk Belgeleri Yükle',
        'select_package' => 'Paketini Seç',
    ];

    public const STEP_DESCS = [
        'welcome'        => 'Portal hakkında genel bilgi edinin.',
        'profile'        => 'Profil fotoğrafı ve iletişim bilgilerinizi ekleyin.',
        'meet_senior'    => 'Danışmanınızla tanışın ve ilk mesajınızı gönderin.',
        'first_docs'     => 'Zorunlu belgelerinizi yükleyerek süreci başlatın.',
        'select_package' => 'Size uygun hizmet paketini seçin.',
    ];

    public function isDone(): bool
    {
        return $this->completed_at !== null || $this->skipped_at !== null;
    }
}
