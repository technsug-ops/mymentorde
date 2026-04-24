<?php

/**
 * Dealer Landing Page — canlı sayaç konfigürasyonu.
 *
 * Mekanik:
 *   - Her sayaç historical baseline + real DB count + "günlük büyüme" olarak hesaplanır
 *   - Günlük büyüme deterministic (date+key hash'inden seed alır)
 *   - Dağılım: ~%35 gün 0 artış (boş) · ~%50 gün 1 artış · ~%15 gün 2 artış
 *   - Her ziyaretçiye aynı değer görünür — gerçekçi ve tutarlı
 *
 * .env override:
 *   DEALER_LANDING_HIST_SELLERS=12
 *   DEALER_LANDING_HIST_APPLICATIONS=120
 *   DEALER_LANDING_HIST_STUDENTS=48
 *   DEALER_LANDING_HIST_COMMISSIONS_EUR=18400
 *   DEALER_LANDING_GROWTH_START=2026-04-24 (büyüme hesabı başlangıç tarihi)
 */

return [
    // Historical baseline (sistem öncesi manuel business'tan gerçek rakamlar)
    'historical_sellers' => (int) env('DEALER_LANDING_HIST_SELLERS', 12),
    'historical_applications' => (int) env('DEALER_LANDING_HIST_APPLICATIONS', 120),
    'historical_students' => (int) env('DEALER_LANDING_HIST_STUDENTS', 48),
    'historical_commissions_eur' => (int) env('DEALER_LANDING_HIST_COMMISSIONS_EUR', 18400),

    // Büyüme hesabı başlangıç tarihi — bu tarihten bugüne kadar her gün için
    // deterministic artış uygulanır
    'growth_start_date' => env('DEALER_LANDING_GROWTH_START', '2026-04-24'),

    /**
     * Günlük artış dağılımı:
     *   skip_pct: yüzde kaç gün 0 artış (boş geçer)
     *   single_pct: yüzde kaç gün 1 artış
     *   kalan: 2 artış
     *
     * Sellers için: %95 skip (yeni dealer nadir gelir)
     * Applications: %35 skip, %50 single, %15 double
     * Students: %70 skip, %25 single, %5 double
     * Commissions: applications'a bağlı (application sayısına × multiplier)
     */
    'daily_growth' => [
        'sellers' => [
            'skip_pct'   => 90,  // %90 gün 0
            'single_pct' => 9,   // %9 gün 1
            'double_pct' => 1,   // %1 gün 2 (nadir — büyük event)
        ],
        'applications' => [
            'skip_pct'   => 35,  // %35 gün 0
            'single_pct' => 50,  // %50 gün 1
            'double_pct' => 15,  // %15 gün 2
        ],
        'students' => [
            'skip_pct'   => 75,  // %75 gün 0 (students daha yavaş)
            'single_pct' => 22,  // %22 gün 1
            'double_pct' => 3,   // %3 gün 2
        ],
        // Commissions, applications'a bağlı — her yeni application 180-380€ arası random
        'commissions_eur_per_application' => [180, 380],
    ],
];
