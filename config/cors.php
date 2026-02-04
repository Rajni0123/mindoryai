<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => array_filter([
        // Main domain
        env('MAIN_DOMAIN_URL', env('APP_URL', 'http://localhost')),

        // Chat subdomain
        env('CHAT_SUBDOMAIN_URL'),

        // Admin subdomain
        env('ADMIN_SUBDOMAIN_URL'),

        // Files subdomain
        env('FILES_SUBDOMAIN_URL'),

        // Mobile app production URLs
        env('MOBILE_APP_URL', null),

        // Development URLs (only if APP_ENV=local)
        env('APP_ENV') === 'local' ? 'http://localhost:3000' : null,
        env('APP_ENV') === 'local' ? 'http://localhost:19006' : null,
        env('APP_ENV') === 'local' ? 'exp://192.168.*:19000' : null,
    ]),

    'allowed_origins_patterns' => env('APP_ENV') === 'local' ? [
        // Pattern for local network IPs (only enabled in development)
        '/^http:\/\/192\.168\.\d+\.\d+:\d+$/',
        '/^http:\/\/10\.0\.\d+\.\d+:\d+$/',
    ] : [],

    'allowed_headers' => [
        'Accept',
        'Content-Type',
        'Authorization',
        'X-Requested-With',
        'X-CSRF-TOKEN',
    ],

    'exposed_headers' => [],

    'max_age' => 86400, // 24 hours

    'supports_credentials' => true,

];
