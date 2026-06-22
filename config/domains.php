<?php

return [

    'main' => env('MAIN_DOMAIN', 'localhost'),

    'main_url' => env('MAIN_DOMAIN_URL', env('APP_URL', 'http://localhost')),

    'chat' => env('CHAT_SUBDOMAIN'),

    'chat_url' => env('CHAT_SUBDOMAIN_URL'),

    'chat_use_main_domain' => filter_var(env('CHAT_USE_MAIN_DOMAIN', false), FILTER_VALIDATE_BOOLEAN),

    'admin' => env('ADMIN_SUBDOMAIN'),

    'admin_url' => env('ADMIN_SUBDOMAIN_URL'),

    'api' => env('API_SUBDOMAIN'),

    'api_url' => env('API_SUBDOMAIN_URL'),

];
