<?php

namespace App\Services\Retrieval;

use App\Services\Retrieval\DTO\RetrievalResult;
use App\Services\Retrieval\Questions\MCQDetector;
use App\Services\Retrieval\Questions\QuestionParser;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Enriches chat retrieval with PDF text from Exa document hits (PYQ / exam papers).
 */
class ChatDocumentRetrievalService
{
    public function __construct(
        protected ExaSearchService $exaSearchService,
        protected TemporaryPdfRetriever $temporaryPdfRetriever,
        protected RetrievalSettingsService $settings,
        protected MCQDetector $mcqDetector,
        protected QuestionParser $questionParser,
    ) {}

    public function isDocumentRequest(string $question): bool
    {
        $lower = mb_strtolower($question);

        foreach ([
            'previous year',
            'last year paper',
            'last year',
            'pyq',
            'question paper',
            'sample paper',
            'exam paper',
            'past paper',
            'old paper',
            'paper pdf',
            'prelims paper',
            'mains paper',
        ] as $keyword) {
            if (str_contains($lower, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{context: string, sources: list<string>, has_substantive_content: bool, extracted_chars: int}
     */
    public function enrich(string $question, RetrievalResult $base, ?string $exam = null): array
    {
        $parts = [];
        $sources = $base->sources;
        $extractedChars = 0;

        if ($base->success && $base->context !== '') {
            $parts[] = $base->context;
            $extractedChars += strlen($base->context);
        }

        if (! $this->isDocumentRequest($question) || ! $this->settings->isExaEnabled()) {
            return $this->buildResult($parts, $sources, $extractedChars);
        }

        $hits = $this->exaSearchService->searchChatSources($question, $exam, 6);
        $pdfUrls = [];

        foreach (array_slice($hits, 0, 4) as $hit) {
            $url = (string) ($hit['url'] ?? '');
            $title = (string) ($hit['title'] ?? 'Exam document');

            if ($url === '') {
                continue;
            }

            $sources[] = $title . ' (' . $url . ')';

            $snippet = trim((string) ($hit['snippet'] ?? ''));
            if ($snippet !== '') {
                $parts[] = "[WEB SNIPPET — {$title}]\nSource: {$url}\n\n{$snippet}";
                $extractedChars += strlen($snippet);
            }

            foreach ($this->discoverPdfUrls($url) as $pdfUrl) {
                $pdfUrls[$pdfUrl] = $title;
            }
        }

        $pdfUrls = array_slice($pdfUrls, 0, 2, true);

        foreach ($pdfUrls as $pdfUrl => $title) {
            if (! $this->settings->isTemporaryPdfEnabled()) {
                $parts[] = "[PDF REFERENCE — {$title}]\nSource: {$pdfUrl}";
                continue;
            }

            $extracted = $this->temporaryPdfRetriever->retrieveFromUrl($pdfUrl, $question);
            if ($extracted === '') {
                $parts[] = "[PDF FOUND BUT TEXT NOT EXTRACTED — {$title}]\nSource: {$pdfUrl}";
                continue;
            }

            $extractedChars += strlen($extracted);
            $summary = $this->summarizeExtractedText($extracted);
            $sampleQuestions = $this->formatSampleQuestions($extracted);

            $section = "[EXTRACTED PDF CONTENT — {$title}]\nSource: {$pdfUrl}\n\n{$summary}";
            if ($sampleQuestions !== '') {
                $section .= "\n\n{$sampleQuestions}";
                $extractedChars += strlen($sampleQuestions);
            }

            $parts[] = $section;
        }

        return $this->buildResult($parts, $sources, $extractedChars);
    }

    /**
     * @param  list<string>  $parts
     * @param  list<string>  $sources
     * @return array{context: string, sources: list<string>, has_substantive_content: bool, extracted_chars: int}
     */
    private function buildResult(array $parts, array $sources, int $extractedChars): array
    {
        $context = trim(implode("\n\n---\n\n", array_filter($parts)));
        if (strlen($context) > 9000) {
            $context = substr($context, 0, 9000) . "\n\n[... content truncated before AI handoff ...]";
        }

        return [
            'context' => $context,
            'sources' => array_values(array_unique($sources)),
            'has_substantive_content' => $extractedChars >= 600,
            'extracted_chars' => $extractedChars,
        ];
    }

    /**
     * @return list<string>
     */
    protected function discoverPdfUrls(string $url): array
    {
        $lower = mb_strtolower($url);
        if (str_contains($lower, '.pdf')) {
            return [$url];
        }

        try {
            $response = Http::timeout(12)
                ->withHeaders(['User-Agent' => 'BlinkStudy/1.0 (+https://blinkstudy.in)'])
                ->get($url);

            if (! $response->successful()) {
                return [];
            }

            $html = $response->body();
            if (! is_string($html) || $html === '') {
                return [];
            }

            $base = $this->resolveBaseUrl($url);
            preg_match_all('/href=["\']([^"\']+\.pdf[^"\']*)["\']/i', $html, $matches);

            $urls = [];
            foreach ($matches[1] ?? [] as $href) {
                $href = html_entity_decode(trim($href));
                if ($href === '') {
                    continue;
                }

                if (str_starts_with($href, 'http://') || str_starts_with($href, 'https://')) {
                    $urls[] = $href;
                } elseif (str_starts_with($href, '/')) {
                    $urls[] = $base . $href;
                }
            }

            return array_values(array_unique($urls));
        } catch (\Throwable $e) {
            Log::warning('ChatDocumentRetrieval: HTML PDF discovery failed', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    protected function resolveBaseUrl(string $url): string
    {
        $scheme = parse_url($url, PHP_URL_SCHEME) ?: 'https';
        $host = parse_url($url, PHP_URL_HOST) ?: '';

        return $scheme . '://' . $host;
    }

    protected function summarizeExtractedText(string $text): string
    {
        $clean = trim(preg_replace('/\s+/', ' ', $text) ?? $text);
        if ($clean === '') {
            return '';
        }

        if (strlen($clean) <= 3500) {
            return $clean;
        }

        $head = substr($clean, 0, 1800);
        $tail = substr($clean, -1200);

        return $head . "\n\n[... middle content truncated ...]\n\n" . $tail;
    }

    protected function formatSampleQuestions(string $text): string
    {
        $blocks = array_slice($this->mcqDetector->detectBlocks($text), 0, 6);
        $formatted = [];

        foreach ($blocks as $block) {
            $parsed = $this->questionParser->parse($block);
            if (! $parsed || empty($parsed['question'])) {
                continue;
            }

            $options = [];
            foreach ($parsed['options'] ?? [] as $label => $optionText) {
                $options[] = strtoupper((string) $label) . ') ' . trim((string) $optionText);
            }

            if (count($options) < 2) {
                continue;
            }

            $line = 'Q' . (count($formatted) + 1) . '. ' . trim((string) $parsed['question']) . "\n   " . implode("\n   ", $options);
            if (! empty($parsed['correct_answer'])) {
                $line .= "\n   Answer: " . strtoupper((string) $parsed['correct_answer']);
            }

            $formatted[] = $line;

            if (count($formatted) >= 3) {
                break;
            }
        }

        if ($formatted === []) {
            return '';
        }

        return "SAMPLE QUESTIONS EXTRACTED FROM PDF (present these to the student):\n" . implode("\n\n", $formatted);
    }
}
