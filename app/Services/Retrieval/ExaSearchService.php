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

    /**
     * Retrieve only real-question candidate documents for quiz extraction.
     *
     * @return list<array<string, mixed>>
     */
    public function searchQuizDocuments(string $topic, ?string $subject, int $maxResults = 10): array
    {
        if (! $this->settings->isExaEnabled()) {
            return [];
        }

        $apiKey = \App\Models\FrontendConfig::getValue('retrieval.exa_api_key')
            ?: config('retrieval.exa.api_key');
        if (! $apiKey) {
            return [];
        }

        $query = trim(implode(' ', array_filter([
            $topic,
            $subject,
            'official previous year question paper sample paper exam pdf',
        ])));

        try {
            $payload = [
                'query' => $query,
                'type' => $this->resolveSearchType(new RetrievalQuery(question: $topic, subject: $subject, topic: $topic, feature: 'quiz')),
                'numResults' => min(max($maxResults, 5), (int) config('retrieval.exa.max_results', 10)),
                'contents' => ['text' => ['maxCharacters' => 500]],
            ];

            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'Content-Type' => 'application/json',
            ])
                ->timeout((int) config('retrieval.exa.timeout', 30))
                ->retry(2, 500)
                ->post(rtrim(config('retrieval.exa.base_url'), '/') . '/search', $payload);

            if (! $response->successful()) {
                return [];
            }

            $results = $response->json('results') ?? [];
            if (! is_array($results) || $results === []) {
                return [];
            }

            return collect($results)
                ->map(function (array $item) {
                    $url = (string) ($item['url'] ?? '');
                    $title = (string) ($item['title'] ?? '');
                    $pdfName = $title !== '' ? $title : basename(parse_url($url, PHP_URL_PATH) ?: '');

                    return [
                        'url' => $url,
                        'title' => $title,
                        'pdf_name' => $pdfName,
                        'exam' => $this->inferExamFromTitle($title),
                        'year' => $this->inferYear($title . ' ' . $url),
                    ];
                })
                ->filter(fn (array $item) => $this->isQuestionDocument($item['url'], $item['title']))
                ->values()
                ->all();
        } catch (\Throwable $e) {
            Log::warning('Exa quiz document search failed', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * Broader Exa search for chat enrichment (HTML listing pages + PDFs).
     *
     * @return list<array{url: string, title: string, snippet: string}>
     */
    public function searchChatSources(string $topic, ?string $exam, int $maxResults = 8): array
    {
        if (! $this->settings->isExaEnabled()) {
            return [];
        }

        $apiKey = \App\Models\FrontendConfig::getValue('retrieval.exa_api_key')
            ?: config('retrieval.exa.api_key');
        if (! $apiKey) {
            return [];
        }

        $query = trim(implode(' ', array_filter([
            $exam,
            $topic,
            'previous year question paper official pdf',
        ])));

        $official = $this->fetchChatSourceResults($query, $apiKey, $maxResults, officialOnly: true);
        if ($official !== []) {
            return $official;
        }

        return $this->fetchChatSourceResults($query, $apiKey, $maxResults, officialOnly: false);
    }

    /**
     * @return list<array{url: string, title: string, snippet: string}>
     */
    protected function fetchChatSourceResults(string $query, string $apiKey, int $maxResults, bool $officialOnly): array
    {
        try {
            $payload = [
                'query' => $query,
                'type' => (string) config('retrieval.exa.search_type', 'auto'),
                'numResults' => min(max($maxResults, 5), (int) config('retrieval.exa.max_results', 10)),
                'contents' => [
                    'text' => ['maxCharacters' => 4000, 'verbosity' => 'compact'],
                ],
            ];

            if ($officialOnly) {
                $officialDomains = PyqSourceFilter::officialDomains();
                if ($officialDomains !== []) {
                    $payload['includeDomains'] = $officialDomains;
                }
            }

            $blocked = PyqSourceFilter::blockedDomains();
            if ($blocked !== []) {
                $payload['excludeDomains'] = $blocked;
            }

            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'Content-Type' => 'application/json',
            ])
                ->timeout((int) config('retrieval.exa.timeout', 30))
                ->retry(2, 500)
                ->post(rtrim(config('retrieval.exa.base_url'), '/') . '/search', $payload);

            if (! $response->successful()) {
                return [];
            }

            $results = $response->json('results') ?? [];
            if (! is_array($results)) {
                return [];
            }

            return collect($results)
                ->map(function (array $item) {
                    $url = (string) ($item['url'] ?? '');
                    $title = (string) ($item['title'] ?? '');

                    return [
                        'url' => $url,
                        'title' => $title !== '' ? $title : basename(parse_url($url, PHP_URL_PATH) ?: 'Document'),
                        'snippet' => $this->extractResultContent($item),
                    ];
                })
                ->filter(function (array $item) {
                    if ($item['url'] === '' || PyqSourceFilter::isBlockedUrl($item['url'])) {
                        return false;
                    }

                    return $this->isChatSourceRelevant($item['url'], $item['title']);
                })
                ->values()
                ->all();
        } catch (\Throwable $e) {
            Log::warning('Exa chat source search failed', [
                'official_only' => $officialOnly,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    protected function isChatSourceRelevant(string $url, string $title): bool
    {
        $haystack = mb_strtolower($url . ' ' . $title);

        foreach ([
            'previous year', 'question paper', 'pyq', 'examination', 'upsc', 'civil services',
            'prelims', 'mains', 'csat', 'sample', 'ncert', 'neet', 'jee', 'nta', '.pdf', 'paper',
        ] as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return str_contains($haystack, '.pdf');
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

                if ($url !== '' && PyqSourceFilter::isBlockedUrl($url)) {
                    continue;
                }

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
        $isDocumentRequest = (bool) ($query->metadata['document_request'] ?? false);
        $useHighlights = ! $isDocumentRequest && (bool) config('retrieval.exa.use_highlights', true);

        $contents = $useHighlights
            ? ['highlights' => true]
            : ['text' => ['maxCharacters' => $isDocumentRequest ? 12000 : 8000, 'verbosity' => 'compact']];

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
        $blockedDomains = PyqSourceFilter::blockedDomains();

        if ($isDocumentRequest) {
            $officialDomains = PyqSourceFilter::officialDomains();
            if ($officialDomains !== []) {
                $payload['includeDomains'] = array_values($officialDomains);
            }
            $excludeDomains = array_values(array_unique(array_merge(
                is_array($excludeDomains) ? $excludeDomains : [],
                $blockedDomains
            )));
        } elseif (is_array($includeDomains) && $includeDomains !== []) {
            $payload['includeDomains'] = array_values($includeDomains);
        }

        if (is_array($excludeDomains) && $excludeDomains !== []) {
            $payload['excludeDomains'] = array_values($excludeDomains);
        } elseif (! $isDocumentRequest && $blockedDomains !== []) {
            $payload['excludeDomains'] = $blockedDomains;
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

    protected function isQuestionDocument(string $url, string $title): bool
    {
        $haystack = mb_strtolower($url . ' ' . $title);
        $looksPdf = str_contains(mb_strtolower($url), '.pdf') || str_contains($haystack, 'pdf');
        if (! $looksPdf) {
            return false;
        }

        foreach (['previous year', 'sample paper', 'question bank', 'exam paper', 'official', 'mcq'] as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    protected function inferExamFromTitle(string $text): string
    {
        $lower = mb_strtolower($text);
        foreach (['jee', 'neet', 'upsc', 'ssc', 'cbse', 'icse', 'gate', 'cat'] as $exam) {
            if (str_contains($lower, $exam)) {
                return strtoupper($exam);
            }
        }

        return '';
    }

    protected function inferYear(string $text): ?int
    {
        if (preg_match('/\b(19|20)\d{2}\b/', $text, $m) === 1) {
            return (int) $m[0];
        }

        return null;
    }
}
