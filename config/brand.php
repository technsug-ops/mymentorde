<?php

/**
 * White-label Brand Configuration
 * ────────────────────────────────────────────────────────────────────
 * Bu dosya tüm marka bilgilerini tek merkezden yönetir.
 * Yeni bir müşteri kurulumunda sadece .env'i doldurmak yeterlidir.
 *
 * Hiçbir hardcoded "MentorDE" referansı bırakmamak için tüm
 * kullanıcıya görünen metinler buradan okunmalıdır:
 *   {{ config('brand.name') }}
 *   {{ config('brand.legal_name') }}
 *   {{ config('brand.email') }}
 */

return [
    // ── Temel kimlik ─────────────────────────────────────────────────
    'name'         => env('BRAND_NAME', 'MentorDE'),
    'legal_name'   => env('BRAND_LEGAL_NAME', env('BRAND_NAME', 'MentorDE')),
    'short_name'   => env('BRAND_SHORT_NAME', env('BRAND_NAME', 'MentorDE')),
    'tagline'      => env('BRAND_TAGLINE', 'Almanya Eğitim Danışmanlığı'),
    'accent'       => env('BRAND_ACCENT', 'DE'),

    // ── Logo & görsel ────────────────────────────────────────────────
    'logo_url'     => env('BRAND_LOGO_URL', ''),
    'logo_path'    => env('BRAND_LOGO_PATH', ''),
    'logo_height'  => env('BRAND_LOGO_HEIGHT', 40),
    'favicon_url'  => env('BRAND_FAVICON_URL', '/favicon.ico'),

    // ── İletişim ─────────────────────────────────────────────────────
    'email'        => env('BRAND_EMAIL', 'info@example.com'),
    'support_email'=> env('BRAND_SUPPORT_EMAIL', env('BRAND_EMAIL', 'destek@example.com')),
    'phone'        => env('BRAND_PHONE', ''),
    'address'      => env('BRAND_ADDRESS', ''),
    'website'      => env('BRAND_WEBSITE', env('APP_URL', 'https://example.com')),

    // ── Mail kimliği (mailler bu isimle çıkacak) ─────────────────────
    'mail_from_name'    => env('MAIL_FROM_NAME', env('BRAND_NAME', 'MentorDE')),
    'mail_from_address' => env('MAIL_FROM_ADDRESS', env('BRAND_EMAIL', 'noreply@example.com')),

    // ── Hukuki ──────────────────────────────────────────────────────
    'company_no'   => env('BRAND_COMPANY_NO', ''),
    'tax_id'       => env('BRAND_TAX_ID', ''),
    'kvkk_url'     => env('BRAND_KVKK_URL', ''),
    'terms_url'    => env('BRAND_TERMS_URL', ''),
    'privacy_url'  => env('BRAND_PRIVACY_URL', ''),

    // ── Sosyal medya ─────────────────────────────────────────────────
    'social' => [
        'instagram' => env('BRAND_INSTAGRAM', ''),
        'twitter'   => env('BRAND_TWITTER', ''),
        'youtube'   => env('BRAND_YOUTUBE', ''),
        'linkedin'  => env('BRAND_LINKEDIN', ''),
        'tiktok'    => env('BRAND_TIKTOK', ''),
        'facebook'  => env('BRAND_FACEBOOK', ''),
    ],

    // ── Tema renkleri (opsiyonel - DB override eder) ─────────────────
    'theme' => [
        'primary'    => env('BRAND_COLOR_PRIMARY', '#2563eb'),
        'primary_2'  => env('BRAND_COLOR_PRIMARY_2', '#1d4ed8'),
        'success'    => env('BRAND_COLOR_SUCCESS', '#16a34a'),
        'warning'    => env('BRAND_COLOR_WARNING', '#d97706'),
        'danger'     => env('BRAND_COLOR_DANGER', '#dc2626'),
    ],
];
