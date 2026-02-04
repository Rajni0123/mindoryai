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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URL'),
        'credentials_path' => env('GOOGLE_APPLICATION_CREDENTIALS', storage_path('app/google-credentials.json')),
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'project_id' => env('GOOGLE_CLOUD_PROJECT_ID'),
        'credentials_path' => env('GOOGLE_APPLICATION_CREDENTIALS', storage_path('app/google-credentials.json')),
    ],

    'ffmpeg' => [
        'path' => env('FFMPEG_PATH', 'ffmpeg'),
        'ffprobe_path' => env('FFPROBE_PATH', 'ffprobe'),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('AI_MODEL', 'gpt-4o'),
    ],

    'renflair' => [
        'api_key' => env('RENFLAIR_API_KEY'),
    ],

    'cashfree' => [
        'app_id' => env('CASHFREE_APP_ID'),
        'secret_key' => env('CASHFREE_SECRET_KEY'),
        'environment' => env('CASHFREE_ENVIRONMENT', 'test'), // 'test' or 'production'
    ],

    'python_ai' => [
        'url'       => env('PYTHON_AI_URL', 'http://127.0.0.1:8100'),
        'api_key'   => env('PYTHON_AI_KEY', 'blinkstudy-ai-secret-2026'),
        'timeout'   => (int) env('PYTHON_AI_TIMEOUT', 15),
        'cache_ttl' => (int) env('PYTHON_AI_CACHE_TTL', 3600),
    ],

    'main_domain' => env('MAIN_DOMAIN', 'example.com'),

    'support_email' => env('SUPPORT_EMAIL', 'support@' . env('MAIN_DOMAIN', 'example.com')),

    'admin_email' => env('ADMIN_EMAIL', 'admin@' . env('MAIN_DOMAIN', 'example.com')),

    'subdomains' => [
        'admin_url' => env('ADMIN_SUBDOMAIN_URL'),
        'chat_url' => env('CHAT_SUBDOMAIN_URL'),
    ],

];
