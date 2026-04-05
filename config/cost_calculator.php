<?php

/**
 * Maliyet Hesaplama Aracı Konfigürasyonu
 * Almanya'da yaşam maliyeti + başvuru giderleri
 */
return [

    // Kaynak: Studentenwerk 2025-2026 ortalamaları + DSW öğrenci yaşam maliyeti raporu
    // Kira: WG/yurt ort. | Gıda: ev pişirme dahil | Ulaşım: Semesterticket hariç ek | Diğer: telefon+kişisel
    'cities' => [
        'berlin'    => ['label' => 'Berlin',    'rent_avg' => 580, 'food_avg' => 270, 'transport_avg' => 86, 'misc_avg' => 100],
        'munich'    => ['label' => 'München',   'rent_avg' => 750, 'food_avg' => 300, 'transport_avg' => 79, 'misc_avg' => 120],
        'hamburg'   => ['label' => 'Hamburg',   'rent_avg' => 530, 'food_avg' => 260, 'transport_avg' => 75, 'misc_avg' => 100],
        'cologne'   => ['label' => 'Köln',      'rent_avg' => 500, 'food_avg' => 250, 'transport_avg' => 69, 'misc_avg' =>  90],
        'frankfurt' => ['label' => 'Frankfurt', 'rent_avg' => 640, 'food_avg' => 270, 'transport_avg' => 86, 'misc_avg' => 100],
        'stuttgart' => ['label' => 'Stuttgart', 'rent_avg' => 580, 'food_avg' => 260, 'transport_avg' => 73, 'misc_avg' =>  90],
        'dortmund'  => ['label' => 'Dortmund',  'rent_avg' => 430, 'food_avg' => 230, 'transport_avg' => 60, 'misc_avg' =>  80],
        'duisburg'  => ['label' => 'Duisburg',  'rent_avg' => 410, 'food_avg' => 220, 'transport_avg' => 60, 'misc_avg' =>  80],
        'other'     => ['label' => 'Diğer',     'rent_avg' => 480, 'food_avg' => 240, 'transport_avg' => 70, 'misc_avg' =>  90],
    ],

    'fixed_costs' => [
        'blocked_account'  => ['label' => 'Bloke Hesap (Sperrkonto)',     'amount' => 11208, 'type' => 'one_time',  'required' => true,  'is_deposit' => true],
        'health_insurance' => ['label' => 'Sağlık Sigortası (yıllık)',    'amount' => 1200,  'type' => 'yearly',    'required' => true],
        'visa_fee'         => ['label' => 'Vize Harcı',                   'amount' => 75,    'type' => 'one_time',  'required' => true],
        'uni_assist'       => ['label' => 'Uni-Assist Ücreti',            'amount' => 75,    'type' => 'one_time',  'required' => false],
        'translation_cert' => ['label' => 'Yeminli Tercüme (tahmini)',    'amount' => 200,   'type' => 'one_time',  'required' => false],
        'flight'           => ['label' => 'Uçak Bileti (tahmini)',        'amount' => 300,   'type' => 'one_time',  'required' => true],
        'semester_ticket'  => ['label' => 'Semesterticket (dönemlik)',    'amount' => 200,   'type' => 'semester',  'required' => false],
    ],

    'tuition' => [
        'de_public'    => 0,      // Devlet üniversitesi — ücretsiz
        'de_bw_public' => 1500,   // Baden-Württemberg (dönemlik)
        'de_private'   => 5000,   // Özel üniversite (dönemlik ortalama)
    ],

    // Türkiye karşılaştırma — TRY cinsinden yıllık özel üniversite maliyeti
    'turkey_private_yearly_try' => 150000,

];
