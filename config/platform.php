<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Impersonation (Platform Owner -> Customer Manager paneline gecis)
    |--------------------------------------------------------------------------
    |
    | Veri guvenligi / DSGVO geregi VARSAYILAN KAPALI. Platform Owner musteri
    | sirketlerinin verisine dogrudan girememeli. Acmak icin gizli bir consent
    | akisi gerekir; o zamana kadar .env'de PLATFORM_IMPERSONATION_ENABLED=true
    | yapilmadikca "Gir" butonu gizli ve impersonate route'u 403 doner.
    |
    */
    'impersonation_enabled' => (bool) env('PLATFORM_IMPERSONATION_ENABLED', false),

];
