<?php

return [

    /*
    |--------------------------------------------------------------------------
    | E-posta doğrulama kuralı
    |--------------------------------------------------------------------------
    |
    | Production'da `dns` da kontrol edilir: alan adının gerçekten MX/A kaydı
    | olmalı — kullanıcı "gmial.com" yazınca formda yakalanır.
    |
    | Test ortamında bu kapalıdır (phpunit.xml → VALIDATION_EMAIL_RULE):
    |   • DNS sorgusu ağ erişimi ister; testler çevrimdışı da koşabilmeli
    |   • Testler `@mentorde.local` gibi çözülmeyen adresler kullanıyor ve
    |     doğrulama sessizce düşüp "kullanıcı oluşmadı" gibi kafa karıştırıcı
    |     hatalara yol açıyordu (5 marketing testi bu yüzden kırmızıydı)
    |
    */

    'email' => env('VALIDATION_EMAIL_RULE', 'email:rfc,dns'),

];
