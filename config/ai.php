<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default AI Provider
    |--------------------------------------------------------------------------
    |
    | The default AI provider to use when none is specified.
    | Options: openai, claude, deepseek, grok
    |
    */

    'default_provider' => env('AI_DEFAULT_PROVIDER', 'openai'),

    /*
    |--------------------------------------------------------------------------
    | OpenAI Configuration
    |--------------------------------------------------------------------------
    */

    'openai' => [
        'api_key' => env('OPENAI_API_KEY', ''),
        'model' => env('OPENAI_MODEL', 'gpt-4o'),
        'timeout' => env('OPENAI_TIMEOUT', 30),
        'ssl_verify' => env('OPENAI_SSL_VERIFY', false), // Set false for Windows development
    ],

    /*
    |--------------------------------------------------------------------------
    | Claude (Anthropic) Configuration
    |--------------------------------------------------------------------------
    */

    'claude' => [
        'api_key' => env('CLAUDE_API_KEY', ''),
        'model' => env('CLAUDE_MODEL', 'claude-3-5-sonnet-20241022'),
        'timeout' => env('CLAUDE_TIMEOUT', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | DeepSeek Configuration
    |--------------------------------------------------------------------------
    */

    'deepseek' => [
        'api_key' => env('DEEPSEEK_API_KEY', ''),
        'model' => env('DEEPSEEK_MODEL', 'deepseek-chat'),
        'timeout' => env('DEEPSEEK_TIMEOUT', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Grok (X.AI) Configuration
    |--------------------------------------------------------------------------
    */

    'grok' => [
        'api_key' => env('GROK_API_KEY', ''),
        'model' => env('GROK_MODEL', 'grok-beta'),
        'timeout' => env('GROK_TIMEOUT', 30),
    ],

];
