<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuestOnboardingStep extends Model
{
    public $timestamps = false;

    protected $fillable = ['guest_application_id', 'step_code', 'completed_at', 'skipped_at'];

    protected $casts = [
        'completed_at' => 'datetime',
        'skipped_at'   => 'datetime',
        'created_at'   => 'datetime',
    ];

    public const STEPS = ['welcome', 'profile', 'meet_senior', 'first_docs', 'explore'];

    public const STEP_LABELS = [
        'welcome'     => 'Hoş Geldin!',
        'profile'     => 'Profilini Tamamla',
        'meet_senior' => 'Danışmanını Tanı',
        'first_docs'  => 'İlk Belgeni Yükle',
        'explore'     => 'Paketleri İncele',
    ];

    public const STEP_ICONS = [
        'welcome'     => '🎉',
        'profile'     => '👤',
        'meet_senior' => '🤝',
        'first_docs'  => '📄',
        'explore'     => '📦',
    ];

    public const STEP_URLS = [
        'welcome'     => '/guest/dashboard',
        'profile'     => '/guest/profile',
        'meet_senior' => '/guest/messages',
        'first_docs'  => '/guest/registration/documents',
        'explore'     => '/guest/services',
    ];
}
