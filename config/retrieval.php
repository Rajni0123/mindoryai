<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Hybrid Retrieval Engine (Perplexity-style orchestration)
    |--------------------------------------------------------------------------
    | All features are opt-in via admin settings. Existing RAG remains default
    | when hybrid mode is disabled.
    */

    'enabled' => env('RETRIEVAL_HYBRID_ENABLED', false),

    'features' => [
        'existing_rag' => env('RETRIEVAL_EXISTING_RAG', true),
        'exa_search' => env('RETRIEVAL_EXA_ENABLED', false),
        'hybrid_mode' => env('RETRIEVAL_HYBRID_MODE', false),
        'redis_cache' => env('RETRIEVAL_REDIS_CACHE', true),
        'web_search' => env('RETRIEVAL_WEB_SEARCH', false),
        'temporary_pdf' => env('RETRIEVAL_TEMP_PDF', true),
        'ai_quiz_fallback' => env('RETRIEVAL_AI_QUIZ_FALLBACK', true),
    ],

    'exa' => [
        'api_key' => env('EXA_API_KEY'),
        'base_url' => env('EXA_BASE_URL', 'https://api.exa.ai'),
        'timeout' => (int) env('EXA_TIMEOUT', 30),
        'max_results' => (int) env('EXA_MAX_RESULTS', 5),
    ],

    'cache' => [
        'prefix' => 'retrieval:',
        'ttl' => [
            'search' => (int) env('RETRIEVAL_CACHE_SEARCH_TTL', 3600),
            'pdf' => (int) env('RETRIEVAL_CACHE_PDF_TTL', 1800),
            'chunks' => (int) env('RETRIEVAL_CACHE_CHUNKS_TTL', 86400),
            'embeddings' => (int) env('RETRIEVAL_CACHE_EMBEDDINGS_TTL', 604800),
            'llm' => (int) env('RETRIEVAL_CACHE_LLM_TTL', 1800),
        ],
    ],

    'temporary_pdf' => [
        'ttl_minutes' => (int) env('RETRIEVAL_TEMP_PDF_TTL_MINUTES', 60),
        'max_size_mb' => (int) env('RETRIEVAL_TEMP_PDF_MAX_MB', 15),
    ],

    'similarity_threshold' => (float) env('RETRIEVAL_SIMILARITY_THRESHOLD', 0.6),
    'top_k' => (int) env('RETRIEVAL_TOP_K', 5),

    /*
    | Default provider priority (admin can override via settings JSON).
    | Lower number = higher priority.
    */
    'provider_priority' => [
        'pyq' => 1,
        'ncert' => 2,
        'teacher_notes' => 3,
        'formula' => 4,
        'custom' => 5,
        'exa' => 6,
        'ai_generation' => 7,
    ],

    'quiz_priority' => [
        'pyq',
        'question_bank',
        'teacher_pdf',
        'sample_paper',
        'exa',
        'ai_generation',
    ],

    'intents' => [
        'tutor' => ['explain', 'teach', 'what is', 'how does', 'define', 'formula', 'theorem'],
        'quiz' => ['quiz', 'mcq', 'test me', 'practice questions'],
        'scan' => ['scan', 'solve this', 'image', 'photo'],
        'revision' => ['revise', 'revision', 'recap', 'flashcard'],
        'pyq' => ['previous year', 'pyq', 'past paper', 'last year question'],
        'current_affairs' => ['current affairs', 'latest news', 'today news'],
        'government' => ['government notification', 'circular', 'official notice', 'upsc notification'],
        'exam_update' => ['exam date', 'exam update', 'admit card', 'result date', 'neet update', 'jee update'],
        'general_search' => ['search', 'find', 'latest', 'recent'],
    ],

];
