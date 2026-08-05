<?php

/**
 * SaaS Subscription Tiers — MentorDE Platform.
 *
 * Her tier 4 alana sahip:
 *   - label    : UI'da gosterilen isim (TR)
 *   - mrr_eur  : aylik tekrarlayan gelir (€), 0 = trial/ucretsiz
 *   - modules  : bu tier'in actigi modul listesi VEYA '*' (hepsi acik)
 *   - limits   : kullanim sinirlari (leads_max, students_max, doc_request_monthly)
 *                NULL = sinirsiz
 *
 * LIMITLER NE ICIN: platform sahibi (SaaS saglayici) musterinin operasyonuna
 * degil KAPASITESINE bakar. "Bu firma paketinin sinirina dayandi, ust pakete
 * gecmeli mi" sorusunun cevabi buradan cikar. Satis hunisi (aday durumlari,
 * kim ne asamada) musterinin kendi isidir; platform konsolunda gosterilmez.
 *
 * Kullanim:
 *   $tier = config('subscription_tiers.gold');
 *   $modules = $tier['modules']; // array veya '*'
 *
 * Module listesi ile App\Support\ModuleAccess::MODULE_META senkron tutulmalidir.
 * Yeni modul eklendiginde tier'lara da dagit (premium = hepsi, gold = uygun olanlar,
 * basic = sadece temel).
 */

return [
    'trial' => [
        'label'    => 'Trial (14 gün)',
        'mrr_eur'  => 0,
        'modules'  => ['core', 'application_guides'],
        'limits'   => [
            'leads_max'            => 100,
            'students_max'         => 25,
            'doc_request_monthly'  => 10,
        ],
    ],

    'basic' => [
        'label'    => 'Basic',
        'mrr_eur'  => 49,
        'modules'  => [
            'core',
            'application_guides',
            'doc_request',
            'manager_password_reset',
        ],
        'limits'   => [
            'leads_max'            => 400,
            'students_max'         => 100,
            'doc_request_monthly'  => 50,
        ],
    ],

    'gold' => [
        'label'    => 'Gold',
        'mrr_eur'  => 149,
        'modules'  => [
            'core',
            'application_guides',
            'doc_request',
            'manager_password_reset',
            'booking',
            'dam',
            'content_hub',
            'multi_provider_ai',
            'doc_builder_ai',
            'ai_labs',
            'dealer',
            'page_visibility',
        ],
        'limits'   => [
            'leads_max'            => 2000,
            'students_max'         => 500,
            'doc_request_monthly'  => 250,
        ],
    ],

    'premium' => [
        'label'    => 'Premium (Hepsi)',
        'mrr_eur'  => 299,
        'modules'  => '*', // tum modullere acik — ModuleAccess::allModules()
        'limits'   => [
            'leads_max'            => null,
            'students_max'         => null,
            'doc_request_monthly'  => null,
        ],
    ],
];
