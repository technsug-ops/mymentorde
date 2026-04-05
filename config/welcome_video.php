<?php

/**
 * Hoş Geldin Videosu Konfigürasyonu
 * Portal girişinden 5 saniye sonra otomatik gösterilir.
 * localStorage'da "bir daha gösterme" tercihi saklanır.
 */

return [

    'guest' => [
        'enabled'    => true,
        'youtube_id' => 'LzLOhMsjpsw',
        'title'      => 'MentorDE Misafir Portalına Hoş Geldin!',
        'subtitle'   => 'Almanya\'da eğitim sürecinizi nasıl yönetebileceğinizi keşfedin.',
    ],

    'student' => [
        'enabled'    => true,
        'youtube_id' => 'LzLOhMsjpsw',
        'title'      => 'MentorDE Öğrenci Portalına Hoş Geldin!',
        'subtitle'   => 'Başvuru sürecinizi, belgelerinizi ve danışmanınızı buradan yönetin.',
    ],

    'dealer' => [
        'enabled'    => true,
        'youtube_id' => '03BI-40UuS4',
        'title'      => 'MentorDE Bayi Portalına Hoş Geldin!',
        'subtitle'   => 'Öğrenci takibi, komisyon ve performans raporlarınıza buradan ulaşın.',
    ],

];
