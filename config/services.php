<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'google' => [
        'client_id'     => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect'      => env('GOOGLE_REDIRECT_URI', '/auth/google/callback'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'ai_writer' => [
        'enabled' => (bool) env('AI_WRITER_ENABLED', false),
        'provider' => env('AI_WRITER_PROVIDER', 'openai_compatible'),
        'base_url' => rtrim((string) env('AI_WRITER_BASE_URL', 'https://api.openai.com/v1'), '/'),
        'api_key' => env('AI_WRITER_API_KEY'),
        'model' => env('AI_WRITER_MODEL', 'gpt-4o-mini'),
        'timeout' => (int) env('AI_WRITER_TIMEOUT', 30),
    ],

    'giphy' => [
        'key' => env('GIPHY_API_KEY'),
    ],

    'whatsapp' => [
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'token'           => env('WHATSAPP_ACCESS_TOKEN'),
        'verify_token'    => env('WHATSAPP_VERIFY_TOKEN', 'mentorde_verify'),
        'api_version'     => env('WHATSAPP_API_VERSION', 'v19.0'),
    ],

    'stripe' => [
        'key'            => env('STRIPE_KEY'),
        'secret'         => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

];
