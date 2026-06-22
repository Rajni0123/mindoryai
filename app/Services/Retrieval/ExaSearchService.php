<?php

namespace App\Services\Retrieval;

use App\Services\Retrieval\DTO\RetrievalQuery;
use App\Services\Retrieval\DTO\RetrievalResult;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Exa /search integration for hybrid retrieval.
 *
 * @see https://docs.exa.ai/reference/search-api-guide-for-coding-agents
 */
class ExaSearchService
{
    public function __construct(
        protected RetrievalCacheService $cache,
        protected RetrievalSettingsService $settings,
        protected ?TemporaryPdfRetriever $temporaryPdfRetriever = null,
    ) {}

    public function search(RetrievalQuery $query, int $maxResults = 5): RetrievalResult
    {
        if (! $this->settings->isExaEnabled()) {
            return RetrievalResult::empty('exa', 'Exa search disabled.');
        }

        $apiKey = \App\Models\FrontendConfig::getValue('retrieval.exa_api_key')
            ?: config('retrieval.exa.api_key');
        if (! $apiKey) {
            return RetrievalResult::empty('exa', 'Exa API key not configured.');
        }

        $cacheKey = $query->normalized() . '|' . ($query->exam ?? '') . '|' . ($query->subject ?? '');

        return $this->cache->remember('search', $cacheKey, function () use ($query, $apiKey, $maxResults) {
            return $this->performSearch($query, $apiKey, $maxResults);
        }, config('retrieval.cache.ttl.search'));
    }

    protected function performSearch(RetrievalQuery $query, string $apiKey, int $maxResults): RetrievalResult
    {
        try {
            $payload = $this->buildSearchPayload($query, $maxResults);

            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'Content-Type' => 'application/json',
            ])
                ->timeout((int) config('retrieval.exa.timeout', 30))
                ->retry(2, 500)
                ->post(rtrim(config('retrieval.exa.base_url'), '/') . '/search', $payload);

            if (! $response->successful()) {
                Log::warning('Exa search failed', ['status' => $response->status(), 'body' => $response->body()]);

                return RetrievalResult::empty('exa', 'Exa search request failed.');
            }

            $results = $response->json('results') ?? [];
            if ($results === []) {
                return RetrievalResult::empty('exa', 'No Exa results.');
            }

            $parts = [];
            $sources = [];

            foreach ($results as $index => $item) {
                $title = $item['title'] ?? 'Web Result';
                $url = $item['url'] ?? '';
                $text = $this->extractResultContent($item);

                if ($this->settings->isTemporaryPdfEnabled()
                    && $this->temporaryPdfRetriever
                    && str_ends_with(strtolower($url), '.pdf')
                ) {
                    $pdfContext = $this->temporaryPdfRetriever->retrieveFromUrl($url, $query->question);
                    if ($pdfContext !== '') {
                        $text = $pdfContext;
                    }
                }

                if ($text === '') {
                    continue;
                }

                $sources[] = $title . ($url ? " ({$url})" : '');
                $parts[] = '[Exa ' . ($index + 1) . ": {$title}]\n" . trim($text);
            }

            if ($parts === []) {
                return RetrievalResult::empty('exa', 'Exa returned no usable text.');
            }

            return new RetrievalResult(
                success: true,
                context: implode("\n\n---\n\n", $parts),
                sources: $sources,
                provider: 'exa',
            );
        } catch (\Throwable $e) {
            Log::error('Exa search exception', ['error' => $e->getMessage()]);

            return RetrievalResult::empty('exa', $e->getMessage());
        }
    }

    /**
     * Build POST /search body per Exa coding-agent guide (no deprecated useAutoprompt).
     *
     * @return array<string, mixed>
     */
    protected function buildSearchPayload(RetrievalQuery $query, int $maxResults): array
    {
        $searchType = $this->resolveSearchType($query);
        $useHighlights = (bool) config('retrieval.exa.use_highlights', true);

        $contents = $useHighlights
            ? ['highlights' => true]
            : ['text' => ['maxCharacters' => 8000, 'verbosity' => 'compact']];

        $maxAgeHours = config('retrieval.exa.max_age_hours');
        if ($maxAgeHours !== null && $maxAgeHours !== '') {
            $contents['maxAgeHours'] = (int) $maxAgeHours;
        }

        $payload = [
            'query' => $this->buildQuery($query),
            'type' => $searchType,
            'numResults' => min($maxResults, (int) config('retrieval.exa.max_results', 10)),
            'contents' => $contents,
        ];

        $includeDomains = config('retrieval.exa.include_domains', []);
        $excludeDomains = config('retrieval.exa.exclude_domains', []);

        if (is_array($includeDomains) && $includeDomains !== []) {
            $payload['includeDomains'] = array_values($includeDomains);
        }

        if (is_array($excludeDomains) && $excludeDomains !== []) {
            $payload['excludeDomains'] = array_values($excludeDomains);
        }

        return $payload;
    }

    protected function resolveSearchType(RetrievalQuery $query): string
    {
        $configured = (string) config('retrieval.exa.search_type', 'auto');
        $allowed = ['auto', 'fast', 'instant', 'deep-lite', 'deep', 'deep-reasoning'];

        if (! in_array($configured, $allowed, true)) {
            $configured = 'auto';
        }

        $intent = $query->metadata['intent'] ?? null;
        if ($configured === 'auto' && in_array($intent, ['current_affairs', 'exam_update', 'government', 'general_search'], true)) {
            return 'deep-lite';
        }

        return $configured;
    }

    /**
     * Prefer highlights (token-efficient); fall back to text/snippet shapes.
     */
    protected function extractResultContent(array $item): string
    {
        if (! empty($item['highlights']) && is_array($item['highlights'])) {
            $highlights = array_map(static fn ($h) => is_string($h) ? $h : (string) json_encode($h), $item['highlights']);

            return trim(implode("\n", $highlights));
        }

        if (! empty($item['text'])) {
            if (is_string($item['text'])) {
                return trim($item['text']);
            }

            if (is_array($item['text'])) {
                return trim((string) ($item['text']['content'] ?? $item['text']['body'] ?? ''));
            }
        }

        if (! empty($item['summary'])) {
            return is_string($item['summary']) ? trim($item['summary']) : trim((string) json_encode($item['summary']));
        }

        return trim((string) ($item['snippet'] ?? ''));
    }

    protected function buildQuery(RetrievalQuery $query): string
    {
        $parts = [$query->question];

        if ($query->exam) {
            $parts[] = $query->exam;
        }

        if ($query->subject) {
            $parts[] = $query->subject;
        }

        return implode(' ', $parts);
    }
}
