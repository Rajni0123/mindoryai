<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Account lockout (failed login / OTP verification)
    |--------------------------------------------------------------------------
    */

    'lockout' => [
        'max_attempts' => (int) env('AUTH_LOCKOUT_MAX_ATTEMPTS', 5),
        'lockout_minutes' => (int) env('AUTH_LOCKOUT_MINUTES', 15),
    ],

    /*
    |--------------------------------------------------------------------------
    | Application encryption key (APP_KEY) — used for sessions, cookies, etc.
    | Not JWT, but must be a strong random secret in production.
    |--------------------------------------------------------------------------
    */

    'app_key_min_length' => 32,

    /*
    |--------------------------------------------------------------------------
    | Sanctum API token lifetime bounds (minutes)
    |--------------------------------------------------------------------------
    */

    'sanctum_token_min_minutes' => 15,
    'sanctum_token_max_minutes' => 60,

];
