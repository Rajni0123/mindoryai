<?php

namespace App\Services\Retrieval\Questions;

use App\Services\Retrieval\RetrievalCacheService;

class QuestionRepository
{
    public function __construct(
        protected RetrievalCacheService $cache,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function getOrStore(string $key, callable $loader): array
    {
        return $this->cache->remember(
            'quiz_questions',
            $key,
            fn () => $loader(),
            (int) config('retrieval.cache.ttl.quiz_questions', 86400)
        );
    }
}

