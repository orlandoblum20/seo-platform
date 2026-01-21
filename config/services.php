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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // AI Providers (configured via admin panel, these are just defaults)
    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
        'default_model' => env('ANTHROPIC_MODEL', 'claude-sonnet-4-20250514'),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'default_model' => env('OPENAI_MODEL', 'gpt-4o'),
    ],

    // DNS Providers
    'cloudflare' => [
        'api_key' => env('CLOUDFLARE_API_KEY'),
        'email' => env('CLOUDFLARE_EMAIL'),
        'account_id' => env('CLOUDFLARE_ACCOUNT_ID'),
    ],

    'dnspod' => [
        'api_id' => env('DNSPOD_API_ID'),
        'api_token' => env('DNSPOD_API_TOKEN'),
    ],

    // Keitaro TDS
    'keitaro' => [
        'url' => env('KEITARO_URL'),
        'campaign_id' => env('KEITARO_CAMPAIGN_ID'),
    ],

    // Caddy SSL Manager
    'caddy' => [
        'admin_api' => env('CADDY_ADMIN_API', 'http://localhost:2019'),
        'caddyfile' => env('CADDY_CADDYFILE', '/etc/caddy/Caddyfile'),
        'sites_path' => env('CADDY_SITES_PATH', '/etc/caddy/sites'),
    ],

];
