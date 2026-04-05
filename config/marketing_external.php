<?php

return [
    'default_days' => (int) env('MKTG_EXTERNAL_SYNC_DAYS', 7),

    'providers' => [
        'meta' => [
            'enabled' => (bool) env('MKTG_META_ENABLED', false),
            'api_version' => (string) env('MKTG_META_API_VERSION', 'v21.0'),
            'ad_account_id' => env('MKTG_META_AD_ACCOUNT_ID'),
            'access_token' => env('MKTG_META_ACCESS_TOKEN'),
        ],

        'ga4' => [
            'enabled' => (bool) env('MKTG_GA4_ENABLED', false),
            'property_id' => env('MKTG_GA4_PROPERTY_ID'),
            'credentials' => env('MKTG_GA4_CREDENTIALS', env('GOOGLE_APPLICATION_CREDENTIALS')),
        ],

        'google_ads' => [
            'enabled' => (bool) env('MKTG_GOOGLE_ADS_ENABLED', false),
            'customer_id' => env('MKTG_GOOGLE_ADS_CUSTOMER_ID'),
            'login_customer_id' => env('MKTG_GOOGLE_ADS_LOGIN_CUSTOMER_ID'),
            'developer_token' => env('MKTG_GOOGLE_ADS_DEVELOPER_TOKEN'),
            'access_token' => env('MKTG_GOOGLE_ADS_ACCESS_TOKEN'),
        ],

        'tiktok_ads' => [
            'enabled' => (bool) env('MKTG_TIKTOK_ENABLED', false),
            'advertiser_id' => env('MKTG_TIKTOK_ADVERTISER_ID'),
            'access_token' => env('MKTG_TIKTOK_ACCESS_TOKEN'),
        ],

        'linkedin_ads' => [
            'enabled' => (bool) env('MKTG_LINKEDIN_ENABLED', false),
            'ad_account_id' => env('MKTG_LINKEDIN_AD_ACCOUNT_ID'),
            'access_token' => env('MKTG_LINKEDIN_ACCESS_TOKEN'),
            'refresh_token' => env('MKTG_LINKEDIN_REFRESH_TOKEN'),
            'client_id' => env('MKTG_LINKEDIN_CLIENT_ID'),
            'client_secret' => env('MKTG_LINKEDIN_CLIENT_SECRET'),
        ],

        'instagram' => [
            'enabled' => (bool) env('MKTG_INSTAGRAM_ENABLED', false),
            'ig_user_id' => env('MKTG_INSTAGRAM_USER_ID'),
            // Meta access_token paylaşılır (ayrı token yoksa meta token kullanılır)
            'access_token' => env('MKTG_INSTAGRAM_ACCESS_TOKEN'),
        ],
    ],
];

