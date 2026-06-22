<?php

namespace App\Services\Retrieval\Questions;

use App\Services\Retrieval\ExaSearchService;
use App\Services\Retrieval\RetrievalCacheService;
use App\Services\Retrieval\RetrievalSettingsService;

class RetrievalEngine
{
    public function __construct(
        protected ExaSearchService $exaSearchService,
        protected RetrievalCacheService $cache,
        protected RetrievalSettingsService $settings,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function retrieveQuizDocuments(string $topic, ?string $subject, int $limit): array
    {
        if (! $this->settings->isExaEnabled()) {
            return [];
        }

        $cacheKey = implode('|', ['quiz-docs', mb_strtolower($topic), mb_strtolower((string) $subject), $limit]);

        return $this->cache->remember('search', $cacheKey, function () use ($topic, $subject, $limit) {
            return $this->exaSearchService->searchQuizDocuments($topic, $subject, $limit);
        }, (int) config('retrieval.cache.ttl.search', 3600));
    }
}

