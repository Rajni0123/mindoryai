<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default AI Provider
    |--------------------------------------------------------------------------
    */

    'default_provider' => env('AI_DEFAULT_PROVIDER', 'openai'),

    /*
    |--------------------------------------------------------------------------
    | BlinkStudy Optimal Model Stack (speed + accuracy)
    |--------------------------------------------------------------------------
    |
    | chat_model          → daily doubts, all subjects (fast)
    | chat_complex_model  → JEE/NEET maths, derivations (accurate)
    | vision_model        → scan & solve images (accurate OCR + steps)
    | quiz_model          → MCQ / quiz JSON generation (fast)
    |
    */

    'chat_primary_provider' => env('AI_CHAT_PRIMARY_PROVIDER', 'openai'),
    'chat_fast_mode' => env('AI_CHAT_FAST_MODE', true),
    'adaptive_models' => env('AI_ADAPTIVE_MODELS', true),
    'prefer_streaming' => env('AI_PREFER_STREAMING', true),

    'chat_model' => env('AI_CHAT_MODEL', 'gpt-4o-mini'),
    'chat_complex_model' => env('AI_CHAT_COMPLEX_MODEL', 'gpt-4o'),
    'vision_model' => env('AI_VISION_MODEL', 'gpt-4o'),
    'quiz_model' => env('AI_QUIZ_MODEL', 'gpt-4o-mini'),

    'openai_first_features' => [
        'chat',
        'ai_doubt',
        'scan_solve',
        'pdf_solve',
        'quiz',
        'mcq_generation',
        'exam_prep',
        'math_reasoning',
    ],

    /*
    |--------------------------------------------------------------------------
    | OpenAI Configuration
    |--------------------------------------------------------------------------
    */

    'openai' => [
        'api_key' => env('OPENAI_API_KEY', ''),
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
        'timeout' => env('OPENAI_TIMEOUT', 30),
        'ssl_verify' => env('OPENAI_SSL_VERIFY', false),
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
