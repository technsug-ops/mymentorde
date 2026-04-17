<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromoPopup extends Model
{
    protected $fillable = [
        'company_id', 'title', 'video_url', 'video_type', 'description',
        'target_pages', 'target_roles', 'delay_seconds', 'frequency',
        'priority', 'is_active', 'starts_at', 'ends_at', 'created_by',
    ];

    protected $casts = [
        'target_pages'  => 'array',
        'target_roles'  => 'array',
        'delay_seconds' => 'integer',
        'priority'      => 'integer',
        'is_active'     => 'boolean',
        'starts_at'     => 'datetime',
        'ends_at'       => 'datetime',
    ];

    public const PAGE_OPTIONS = [
        'guest.dashboard'    => 'Aday Öğrenci — Ana Sayfa',
        'guest.registration' => 'Aday Öğrenci — Başvuru Formu',
        'guest.services'     => 'Aday Öğrenci — Hizmetler',
        'guest.timeline'     => 'Aday Öğrenci — Süreç Takvimi',
        'guest.cost'         => 'Aday Öğrenci — Maliyet Hesabı',
        'student.dashboard'  => 'Öğrenci — Ana Sayfa',
        'student.materials'  => 'Öğrenci — Eğitim Materyalleri',
        'senior.dashboard'   => 'Danışman — Ana Sayfa',
        'dealer.dashboard'   => 'Bayi — Ana Sayfa',
        'manager.dashboard'  => 'Manager — Ana Sayfa',
    ];

    public const ROLE_OPTIONS = [
        'guest'   => 'Aday Öğrenci',
        'student' => 'Öğrenci',
        'senior'  => 'Danışman',
        'dealer'  => 'Bayi',
        'manager' => 'Manager',
        'staff'   => 'Staff',
    ];

    public const FREQUENCY_OPTIONS = [
        'first_login'  => 'Sadece İlk Giriş',
        'per_session'  => 'Her Oturum (1 kez)',
        'always'       => 'Her Sayfa Yüklemesi',
    ];

    public function isCurrentlyActive(): bool
    {
        if (!$this->is_active) return false;
        if ($this->starts_at && $this->starts_at->isFuture()) return false;
        if ($this->ends_at && $this->ends_at->isPast()) return false;
        return true;
    }
}
