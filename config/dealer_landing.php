<?php

/**
 * Dealer Landing Page — canlı sayaç baseline değerleri.
 *
 * Her sayaç: historical_baseline + real_db_count olarak hesaplanır.
 * Baseline = sistem öncesi manuel yönetilmiş business'ın gerçek rakamları.
 *
 * .env'den özelleştirilebilir:
 *   DEALER_LANDING_HIST_SELLERS=12
 *   DEALER_LANDING_HIST_APPLICATIONS=120
 *   DEALER_LANDING_HIST_STUDENTS=48
 *   DEALER_LANDING_HIST_COMMISSIONS_EUR=18400
 *
 * Varsayılan: sadece MentorDE'nin 1 yıllık manuel partnerlik döneminden
 * gerçek rakamlar (technsug'un paylaştığına göre ayarlanabilir).
 */

return [
    // Aktif satış ortağı (historical + live user_count where role='dealer')
    'historical_sellers' => (int) env('DEALER_LANDING_HIST_SELLERS', 12),

    // Toplam yönlendirilen aday (historical + live guest_applications)
    'historical_applications' => (int) env('DEALER_LANDING_HIST_APPLICATIONS', 120),

    // Almanya'da eğitim gören öğrenci (historical + live student users)
    'historical_students' => (int) env('DEALER_LANDING_HIST_STUDENTS', 48),

    // Ödenen toplam komisyon EUR (historical + optional dealer payout sum)
    'historical_commissions_eur' => (int) env('DEALER_LANDING_HIST_COMMISSIONS_EUR', 18400),

    /**
     * Pseudo-live increment settings — sayaçların "canlı" hissini verir.
     * Her N saniyede küçük random artış.
     */
    'increment_interval_ms' => 25000, // 25 sn
    'increment_ranges' => [
        'sellers'         => [0, 0], // nadiren artar, 0 bırakıyoruz (gerçekçi)
        'applications'    => [1, 3], // 25 sn'de 1-3 aday
        'students'        => [0, 1], // yavaş
        'commissions_eur' => [50, 350], // 25 sn'de €50-350 artar (aday başına ~€200)
    ],
];
