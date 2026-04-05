<?php

/**
 * Lead Score Konfigürasyonu
 * LeadScoreService ile senkronize — tüm puan değerleri bu dosyadan okunur.
 */
return [

    'tiers' => [
        0   => 'cold',
        20  => 'warm',
        50  => 'hot',
        80  => 'sales_ready',
        100 => 'champion',
    ],

    'tier_labels' => [
        'cold'        => 'Cold',
        'warm'        => 'Warm',
        'hot'         => 'Hot',
        'sales_ready' => 'Sales Ready',
        'champion'    => 'Champion',
    ],

    'factors' => [
        'registration_form_submitted' => ['points' => 15,  'description' => 'Kayit formu tamamlandi'],
        'senior_assigned'             => ['points' => 10,  'description' => 'Senior atandi'],
        'utm_or_dealer_source'        => ['points' => 5,   'description' => 'UTM/Dealer kanali'],
        'contract_requested'          => ['points' => 20,  'description' => 'Sozlesme talep edildi'],
        'contract_signed'             => ['points' => 35,  'description' => 'Sozlesme imzalandi/onaylandi'],
        'outcome_acceptance'          => ['points' => 25,  'description' => 'ProcessOutcome: kabul'],
        'outcome_conditional'         => ['points' => 15,  'description' => 'ProcessOutcome: kosullu kabul'],
        'outcome_rejection'           => ['points' => -10, 'description' => 'ProcessOutcome: ret (ceza)'],
        'university_accepted'         => ['points' => 20,  'description' => 'Universite kabul'],
        'visa_approved'               => ['points' => 35,  'description' => 'Vize belgesi (VIS-ERTEIL) onaylt'],
        'recent_note'                 => ['points' => 10,  'description' => 'Son 14 gunde not var'],
        'recent_appointment'          => ['points' => 5,   'description' => 'Son 30 gunde randevu var'],
        'high_risk_penalty'           => ['points' => -15, 'description' => 'Risk seviyesi high/critical (ceza)'],
    ],

    'min_score'               => 0,
    'max_score'               => 100,
    'recent_note_days'        => 14,
    'recent_appointment_days' => 30,
    'visa_doc_code'           => 'VIS-ERTEIL',
];
