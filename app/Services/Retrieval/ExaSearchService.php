<?php

namespace App\Services\Retrieval;

use App\Contracts\Retrieval\RetrievalProviderInterface;
use App\Services\Retrieval\DTO\RetrievalQuery;
use App\Services\Retrieval\DTO\RetrievalResult;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'Content-Type' => 'application/json',
            ])
                ->timeout((int) config('retrieval.exa.timeout', 30))
                ->retry(2, 500)
                ->post(rtrim(config('retrieval.exa.base_url'), '/') . '/search', [
                    'query' => $this->buildQuery($query),
                    'numResults' => min($maxResults, (int) config('retrieval.exa.max_results', 5)),
                    'useAutoprompt' => true,
                    'type' => 'auto',
                    'contents' => [
                        'text' => true,
                    ],
                ]);

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
                $text = $item['text'] ?? $item['snippet'] ?? '';

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
