<?php

namespace App\Models\Hr;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class HrOnboardingTask extends Model
{
    protected $table = 'hr_onboarding_tasks';

    protected $fillable = [
        'company_id', 'user_id', 'week', 'title',
        'description', 'is_done', 'completed_by',
        'completed_at', 'sort_order',
    ];

    protected $casts = [
        'is_done'      => 'boolean',
        'completed_at' => 'datetime',
    ];

    // V6 kanban onboarding şablonu — yeni çalışana otomatik atanır
    public static array $defaultTasks = [
        '1' => [
            'Karşılama toplantısı (Halil ile 2 saat)',
            'Slack kurulumu + kanal davetleri',
            'Sistem hesap erişimleri (e-posta, ERP, GitHub)',
            'Şirket el kitabını oku',
            'Kanban boardları incele (GROWTH, STUDENT_JOURNEY, PRODUCT)',
        ],
        '2' => [
            'Mevcut çalışandan shadow: iş akışını izle',
            'İlk küçük görevi al ve tamamla',
            'Sorular için Slack\'te ilgili kanalda sor',
            'HR profilini tamamla (hr/my/attendance)',
            'Ekip üyeleriyle tanışma (Slack DM)',
        ],
        '3' => [
            'Bağımsız olarak görev kartı çek',
            'İlk haftanın geri bildirimi (manager ile 1-on-1)',
            'Kendi çalışma stilini ekiple paylaş',
            'Mevcut süreçleri belgele (iyileştirme fırsatı bul)',
        ],
        '4' => [
            'Tam verimlilik: backlog\'dan kart çek',
            '4. hafta değerlendirmesi (manager ile)',
            'Geliştirme önerisi sun (en az 1 madde)',
            'Onboarding tamamlandı olarak işaretle',
        ],
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
