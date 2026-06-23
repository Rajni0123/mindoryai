<?php

namespace App\Services\Retrieval;

use App\Services\Retrieval\DTO\RetrievalResult;
use App\Services\Retrieval\Questions\MCQDetector;
use App\Services\Retrieval\Questions\QuestionParser;
use Illuminate\Support\Facades\Log;

/**
 * Enriches chat retrieval with PDF text from Exa document hits (PYQ / exam papers).
 * Official sources first; open-web PDF fallback when extraction fails.
 */
class ChatDocumentRetrievalService
{
    public function __construct(
        protected ExaSearchService $exaSearchService,
        protected TemporaryPdfRetriever $temporaryPdfRetriever,
        protected RetrievalSettingsService $settings,
        protected MCQDetector $mcqDetector,
        protected QuestionParser $questionParser,
        protected OfficialPyqArchiveCrawler $officialArchiveCrawler,
        protected BroadPyqPdfSearcher $broadPyqPdfSearcher,
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
            'board paper',
            'model paper',
        ] as $keyword) {
            if (str_contains($lower, $keyword)) {
                return true;
            }
        }

        // "last year CBSE class 10 preparation" → wants PYQ/materials, not generic tips
        if (str_contains($lower, 'last year') || str_contains($lower, 'previous year')) {
            foreach (['cbse', 'icse', 'board', 'class 10', 'class 12', '10th', '12th', '10 th', '12 th'] as $examHint) {
                if (str_contains($lower, $examHint)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function extractClassLevel(string $question): ?string
    {
        if (preg_match('/\b(?:class\s*)?10\s*(?:th)?\b/i', $question)) {
            return 'Class 10';
        }
        if (preg_match('/\b(?:class\s*)?12\s*(?:th)?\b/i', $question)) {
            return 'Class 12';
        }

        return null;
    }

    public function buildExamLabel(?string $exam, string $question): string
    {
        $class = $this->extractClassLevel($question);
        $board = null;
        $lower = mb_strtolower($question);

        foreach (['cbse', 'icse', 'nta', 'upsc', 'neet', 'jee'] as $tag) {
            if (str_contains($lower, $tag)) {
                $board = strtoupper($tag);
                break;
            }
        }

        if ($exam) {
            $board = strtoupper($exam);
        }

        if ($board && $class) {
            return "{$board} {$class}";
        }

        return $board ?? $class ?? '';
    }

    /**
     * @return array{
     *   context: string,
     *   sources: list<string>,
     *   has_substantive_content: bool,
     *   extracted_chars: int,
     *   pdf_extracted_chars: int,
     *   has_official_source: bool,
     *   used_broad_fallback: bool
     * }
     */
    public function enrich(string $question, RetrievalResult $base, ?string $exam = null): array
    {
        $parts = [];
        $sources = PyqSourceFilter::filterSourceLabels($base->sources);
        $extractedChars = 0;
        $pdfExtractedChars = 0;
        $hasOfficialSource = false;
        $usedBroadFallback = false;

        if ($base->success && $base->context !== '') {
            $parts[] = $base->context;
            $extractedChars += strlen($base->context);
        }

        if (! $this->isDocumentRequest($question) || ! $this->settings->isExaEnabled()) {
            return $this->buildResult($parts, $sources, $extractedChars, $pdfExtractedChars, $hasOfficialSource, $usedBroadFallback);
        }

        $examLabel = $this->buildExamLabel($exam, $question);
        $searchExam = $examLabel !== '' ? $examLabel : $exam;

        $hits = $this->exaSearchService->searchChatSources($question, $searchExam, 6);
        $pdfUrls = [];

        foreach ($this->officialArchiveCrawler->discoverPdfUrls($searchExam, $question) as $archivePdf) {
            $pdfUrls[$archivePdf] = 'Official archive';
            if (PyqSourceFilter::isOfficialUrl($archivePdf)) {
                $hasOfficialSource = true;
            }
        }

        foreach (array_slice($hits, 0, 4) as $hit) {
            $url = (string) ($hit['url'] ?? '');
            $title = (string) ($hit['title'] ?? 'Exam document');

            if ($url === '' || PyqSourceFilter::isBlockedUrl($url)) {
                continue;
            }

            if (PyqSourceFilter::isOfficialUrl($url)) {
                $hasOfficialSource = true;
            }

            $sources[] = $title . ' (' . $url . ')';

            $snippet = trim((string) ($hit['snippet'] ?? ''));
            if ($snippet !== '') {
                $parts[] = "[WEB SNIPPET — {$title}]\nSource: {$url}\n\n{$snippet}";
                $extractedChars += strlen($snippet);
            }

            foreach ($this->discoverPdfUrls($url) as $pdfUrl) {
                if (! PyqSourceFilter::isBlockedUrl($pdfUrl)) {
                    $pdfUrls[$pdfUrl] = $title;
                }
            }
        }

        [$pdfExtractedChars, $extractedChars] = $this->extractFromPdfMap(
            array_slice($pdfUrls, 0, 2, true),
            $question,
            $parts,
            $sources,
            $pdfExtractedChars,
            $extractedChars,
            $hasOfficialSource
        );

        if ($pdfExtractedChars < 400 && $this->settings->isTemporaryPdfEnabled()) {
            Log::info('ChatDocumentRetrieval: official PDF extract insufficient, trying broad web PYQ fallback');

            $broadDocs = $this->broadPyqPdfSearcher->findPdfDocuments($question, $searchExam, 8);
            $broadPdfUrls = [];

            foreach ($broadDocs as $doc) {
                $url = (string) ($doc['url'] ?? '');
                $title = (string) ($doc['title'] ?? 'Web PYQ PDF');
                if ($url === '') {
                    continue;
                }

                $broadPdfUrls[$url] = $title . ' [' . ($doc['search_provider'] ?? 'web') . ']';
                $sources[] = $title . ' (' . $url . ')';
            }

            foreach ($this->discoverPdfUrlsFromHits($broadDocs) as $url => $title) {
                $broadPdfUrls[$url] = $title;
            }

            if ($broadPdfUrls !== []) {
                $usedBroadFallback = true;
                $parts[] = '[BROAD WEB PYQ SEARCH — official PDF text was not available; scanning open-web PDFs for real questions]';

                [$pdfExtractedChars, $extractedChars] = $this->extractFromPdfMap(
                    array_slice($broadPdfUrls, 0, 4, true),
                    $question,
                    $parts,
                    $sources,
                    $pdfExtractedChars,
                    $extractedChars,
                    $hasOfficialSource,
                    maxQuestions: 8
                );
            }
        }

        return $this->buildResult($parts, $sources, $extractedChars, $pdfExtractedChars, $hasOfficialSource, $usedBroadFallback);
    }

    /**
     * @param  array<string, string>  $pdfUrls
     * @param  list<string>  $parts
     * @param  list<string>  $sources
     * @return array{0: int, 1: int}
     */
    protected function extractFromPdfMap(
        array $pdfUrls,
        string $question,
        array &$parts,
        array &$sources,
        int $pdfExtractedChars,
        int $extractedChars,
        bool &$hasOfficialSource,
        int $maxQuestions = 3,
    ): array {
        foreach ($pdfUrls as $pdfUrl => $title) {
            if (PyqSourceFilter::isOfficialUrl($pdfUrl)) {
                $hasOfficialSource = true;
            }

            if (! $this->settings->isTemporaryPdfEnabled()) {
                $parts[] = "[PDF REFERENCE — {$title}]\nSource: {$pdfUrl}";
                continue;
            }

            $extracted = $this->temporaryPdfRetriever->retrieveFromUrl($pdfUrl, $question);
            if ($extracted === '') {
                $parts[] = "[PDF FOUND BUT TEXT NOT EXTRACTED — {$title}]\nSource: {$pdfUrl}";
                continue;
            }

            $pdfExtractedChars += strlen($extracted);
            $extractedChars += strlen($extracted);
            $summary = $this->summarizeExtractedText($extracted);
            $sampleQuestions = $this->formatSampleQuestions($extracted, $maxQuestions);

            $section = "[EXTRACTED PDF CONTENT — {$title}]\nSource: {$pdfUrl}\n\n{$summary}";
            if ($sampleQuestions !== '') {
                $section .= "\n\n{$sampleQuestions}";
                $extractedChars += strlen($sampleQuestions);
            }

            $parts[] = $section;
            $sources[] = $title . ' (' . $pdfUrl . ')';

            if ($pdfExtractedChars >= 400 && $sampleQuestions !== '') {
                break;
            }
        }

        return [$pdfExtractedChars, $extractedChars];
    }

    /**
     * @param  list<array{url: string, title: string}>  $docs
     * @return array<string, string>
     */
    protected function discoverPdfUrlsFromHits(array $docs): array
    {
        $map = [];
        foreach ($docs as $doc) {
            $url = (string) ($doc['url'] ?? '');
            $title = (string) ($doc['title'] ?? 'PYQ PDF');
            if ($url === '') {
                continue;
            }

            if (str_contains(mb_strtolower($url), '.pdf')) {
                $map[$url] = $title;
                continue;
            }

            foreach ($this->discoverPdfUrls($url) as $pdfUrl) {
                $map[$pdfUrl] = $title;
            }
        }

        return $map;
    }

    /**
     * @param  list<string>  $parts
     * @param  list<string>  $sources
     * @return array{
     *   context: string,
     *   sources: list<string>,
     *   has_substantive_content: bool,
     *   extracted_chars: int,
     *   pdf_extracted_chars: int,
     *   has_official_source: bool,
     *   used_broad_fallback: bool
     * }
     */
    private function buildResult(
        array $parts,
        array $sources,
        int $extractedChars,
        int $pdfExtractedChars = 0,
        bool $hasOfficialSource = false,
        bool $usedBroadFallback = false,
    ): array {
        $context = trim(implode("\n\n---\n\n", array_filter($parts)));
        if (strlen($context) > 12000) {
            $context = substr($context, 0, 12000) . "\n\n[... content truncated before AI handoff ...]";
        }

        $sources = array_values(array_unique($sources));
        if (! $usedBroadFallback) {
            $sources = PyqSourceFilter::filterOfficialSourceLabels($sources);
        } else {
            $sources = PyqSourceFilter::filterSourceLabels($sources);
        }

        $hasSubstantive = $pdfExtractedChars >= 400;

        return [
            'context' => $context,
            'sources' => $sources,
            'has_substantive_content' => $hasSubstantive,
            'extracted_chars' => $extractedChars,
            'pdf_extracted_chars' => $pdfExtractedChars,
            'has_official_source' => $hasOfficialSource,
            'used_broad_fallback' => $usedBroadFallback,
        ];
    }

    /**
     * @return list<string>
     */
    protected function discoverPdfUrls(string $url): array
    {
        return $this->officialArchiveCrawler->scrapePdfLinks($url);
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

    protected function formatSampleQuestions(string $text, int $maxQuestions = 3): string
    {
        $blocks = array_slice($this->mcqDetector->detectBlocks($text), 0, $maxQuestions * 2);
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

            if (count($formatted) >= $maxQuestions) {
                break;
            }
        }

        if ($formatted === []) {
            return '';
        }

        return "SAMPLE QUESTIONS EXTRACTED FROM PDF (present these to the student):\n" . implode("\n\n", $formatted);
    }
}
