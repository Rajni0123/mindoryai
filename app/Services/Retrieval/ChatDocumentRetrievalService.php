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
        protected OfficialPyqArchiveCrawler $officialArchiveCrawler,
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
        $sources = PyqSourceFilter::filterSourceLabels($base->sources);
        $extractedChars = 0;
        $pdfExtractedChars = 0;
        $hasOfficialSource = false;

        if ($base->success && $base->context !== '') {
            $parts[] = $base->context;
            $extractedChars += strlen($base->context);
        }

        if (! $this->isDocumentRequest($question) || ! $this->settings->isExaEnabled()) {
            return $this->buildResult($parts, $sources, $extractedChars, $pdfExtractedChars, $hasOfficialSource);
        }

        $hits = $this->exaSearchService->searchChatSources($question, $exam, 6);
        $pdfUrls = [];

        foreach ($this->officialArchiveCrawler->discoverPdfUrls($exam, $question) as $archivePdf) {
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
            if ($snippet !== '' && ! PyqSourceFilter::isBlockedUrl($url)) {
                $parts[] = "[WEB SNIPPET — {$title}]\nSource: {$url}\n\n{$snippet}";
                $extractedChars += strlen($snippet);
            }

            foreach ($this->discoverPdfUrls($url) as $pdfUrl) {
                if (! PyqSourceFilter::isBlockedUrl($pdfUrl)) {
                    $pdfUrls[$pdfUrl] = $title;
                }
            }
        }

        $pdfUrls = array_slice($pdfUrls, 0, 2, true);

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
            $sampleQuestions = $this->formatSampleQuestions($extracted);

            $section = "[EXTRACTED PDF CONTENT — {$title}]\nSource: {$pdfUrl}\n\n{$summary}";
            if ($sampleQuestions !== '') {
                $section .= "\n\n{$sampleQuestions}";
                $extractedChars += strlen($sampleQuestions);
            }

            $parts[] = $section;
        }

        return $this->buildResult($parts, $sources, $extractedChars, $pdfExtractedChars, $hasOfficialSource);
    }

    /**
     * @param  list<string>  $parts
     * @param  list<string>  $sources
     * @return array{context: string, sources: list<string>, has_substantive_content: bool, extracted_chars: int}
     */
    private function buildResult(
        array $parts,
        array $sources,
        int $extractedChars,
        int $pdfExtractedChars = 0,
        bool $hasOfficialSource = false,
    ): array {
        $context = trim(implode("\n\n---\n\n", array_filter($parts)));
        if (strlen($context) > 9000) {
            $context = substr($context, 0, 9000) . "\n\n[... content truncated before AI handoff ...]";
        }

        $sources = PyqSourceFilter::filterOfficialSourceLabels(array_values(array_unique($sources)));

        // Substantive = real PDF text extracted, NOT coaching-site SEO snippets
        $hasSubstantive = $pdfExtractedChars >= 400;

        return [
            'context' => $context,
            'sources' => $sources,
            'has_substantive_content' => $hasSubstantive,
            'extracted_chars' => $extractedChars,
            'pdf_extracted_chars' => $pdfExtractedChars,
            'has_official_source' => $hasOfficialSource,
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
