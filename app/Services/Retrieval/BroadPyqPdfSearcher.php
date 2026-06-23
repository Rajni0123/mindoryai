<?php

namespace App\Services\Retrieval;

use App\Services\Retrieval\Questions\Search\Providers\BingSearchProvider;
use App\Services\Retrieval\Questions\Search\Providers\BraveSearchProvider;
use App\Services\Retrieval\Questions\Search\Providers\ExaQuizDocumentProvider;
use App\Services\Retrieval\Questions\Search\Providers\GoogleCustomSearchProvider;
use App\Services\Retrieval\Questions\Support\QuizDocumentSupport;
use App\Services\Retrieval\RetrievalCacheService;
use Illuminate\Support\Facades\Log;

/**
 * When official NTA/CBSE PDF text cannot be extracted, search the open web for PYQ PDFs
 * (Google / Exa / Brave / Bing) and return ranked direct PDF links.
 */
class BroadPyqPdfSearcher
{
    public function __construct(
        protected ExaSearchService $exaSearchService,
        protected GoogleCustomSearchProvider $googleSearch,
        protected BraveSearchProvider $braveSearch,
        protected BingSearchProvider $bingSearch,
        protected RetrievalCacheService $cache,
        protected RetrievalSettingsService $settings,
    ) {}

    /**
     * @return list<array{url: string, title: string, search_provider: string}>
     */
    public function findPdfDocuments(string $question, ?string $exam, int $limit = 8): array
    {
        if (! $this->settings->isExaEnabled() && ! $this->googleSearch->isAvailable()) {
            return [];
        }

        $topic = $this->buildTopic($question, $exam);
        $cacheKey = 'broad-pyq|' . md5(mb_strtolower($topic)) . '|' . $limit;

        return $this->cache->remember('search', $cacheKey, function () use ($topic, $exam, $limit) {
            return $this->searchProviders($topic, $exam, $limit);
        }, (int) config('retrieval.cache.ttl.search', 3600));
    }

    /**
     * @return list<array{url: string, title: string, search_provider: string}>
     */
    protected function searchProviders(string $topic, ?string $exam, int $limit): array
    {
        $documents = [];

        if ($this->settings->isExaEnabled()) {
            foreach ($this->exaSearchService->searchBroadPyqPdfs($topic, $exam, max($limit, 8)) as $doc) {
                $documents[] = $doc;
            }
        }

        foreach ([$this->googleSearch, $this->braveSearch, $this->bingSearch] as $provider) {
            if (! $provider->isAvailable()) {
                continue;
            }

            try {
                $results = $provider->search($topic, $exam, max($limit, 8));
                if ($results !== []) {
                    Log::info('BroadPyqPdfSearcher provider hit', [
                        'provider' => $provider->key(),
                        'count' => count($results),
                    ]);
                    $documents = array_merge($documents, $results);
                }
            } catch (\Throwable $e) {
                Log::warning('BroadPyqPdfSearcher provider failed', [
                    'provider' => $provider->key(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $ranked = QuizDocumentSupport::dedupeAndRank($documents, $topic, $exam, $limit);

        return array_values(array_filter(array_map(function (array $doc) {
            $url = (string) ($doc['url'] ?? '');
            if ($url === '' || ! PyqSourceFilter::isAllowedPyqPdfUrl($url)) {
                return null;
            }

            return [
                'url' => $url,
                'title' => (string) ($doc['title'] ?? basename(parse_url($url, PHP_URL_PATH) ?: 'PYQ PDF')),
                'search_provider' => (string) ($doc['search_provider'] ?? 'web'),
            ];
        }, $ranked)));
    }

    protected function buildTopic(string $question, ?string $exam): string
    {
        $lastYear = now()->year - 1;

        return trim(implode(' ', array_filter([
            $exam,
            $question,
            (string) $lastYear,
            'previous year question paper pdf',
        ])));
    }
}
