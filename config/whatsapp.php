<?php

/**
 * Meta WhatsApp Cloud API yapilandirmasi.
 *
 * Doc: https://developers.facebook.com/docs/whatsapp/cloud-api
 *
 * Yapilandirma:
 *   WHATSAPP_PHONE_NUMBER_ID       Phone number ID (Meta Business Manager > WhatsApp > API Setup)
 *   WHATSAPP_ACCESS_TOKEN          Permanent system user access token (24h test token DEGIL)
 *   WHATSAPP_API_VERSION           Graph API surumu (default: v21.0)
 *   WHATSAPP_BUSINESS_ACCOUNT_ID   WhatsApp Business Account (WABA) id (opsiyonel — webhook/template icin)
 *   WHATSAPP_VERIFY_TOKEN          Webhook dogrulama token'i (manager'da set edilir)
 *
 * Bos birakilirsa servis sessizce devre disi olur (Log::warning), uygulamayi
 * crash etmez. Manager UI'da channel="whatsapp" secilirse servis cagri yapar
 * ama config yoksa false doner.
 */
return [
    'enabled' => (bool) env('WHATSAPP_PHONE_NUMBER_ID')
        && (bool) env('WHATSAPP_ACCESS_TOKEN'),

    'phone_number_id'      => env('WHATSAPP_PHONE_NUMBER_ID'),
    'access_token'         => env('WHATSAPP_ACCESS_TOKEN'),
    'api_version'          => env('WHATSAPP_API_VERSION', 'v21.0'),
    'business_account_id'  => env('WHATSAPP_BUSINESS_ACCOUNT_ID'),
    'verify_token'         => env('WHATSAPP_VERIFY_TOKEN', 'mentorde_verify'),

    // Graph API base URL — testlerde mock'lanabilir
    'base_url' => env('WHATSAPP_BASE_URL', 'https://graph.facebook.com'),

    // HTTP timeout (saniye)
    'timeout' => (int) env('WHATSAPP_HTTP_TIMEOUT', 15),

    // Varsayilan template dili (Meta'da onayli template iceriklerinde kullanilir)
    'default_language' => env('WHATSAPP_DEFAULT_LANGUAGE', 'tr'),

    // Telefon normalize varsayilan ulke kodu (Turkiye)
    'default_country_code' => env('WHATSAPP_DEFAULT_COUNTRY_CODE', '90'),
];
