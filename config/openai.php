<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OpenAI API Key
    |--------------------------------------------------------------------------
    |
    | Your OpenAI API key. Get it from https://platform.openai.com/api-keys
    |
    */

    'api_key' => env('OPENAI_API_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | OpenAI API URL
    |--------------------------------------------------------------------------
    |
    | The base URL for OpenAI API endpoints.
    |
    */

    'api_url' => env('OPENAI_API_URL', 'https://api.openai.com/v1/chat/completions'),

    /*
    |--------------------------------------------------------------------------
    | OpenAI Model
    |--------------------------------------------------------------------------
    |
    | The model to use for image analysis. Options:
    | - gpt-4o (GPT-4 Turbo with vision - recommended)
    | - gpt-4-turbo
    |
    */

    'model' => env('OPENAI_MODEL', 'gpt-4o'),

    /*
    |--------------------------------------------------------------------------
    | HTTP Client Options
    |--------------------------------------------------------------------------
    |
    | Options for the HTTP client (Guzzle). Useful for SSL configuration.
    | WARNING: Disabling SSL verification is not recommended for production!
    |
    */

    'http_client_options' => [
        'verify' => env('OPENAI_SSL_VERIFY', false), // Set to false to disable SSL verification (development only)
        'timeout' => env('OPENAI_REQUEST_TIMEOUT', 30),
    ],

];
