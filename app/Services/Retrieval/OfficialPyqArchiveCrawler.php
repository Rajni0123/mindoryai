<?php

namespace App\Services\Retrieval;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Scrapes known government exam archive pages for direct PDF links.
 */
class OfficialPyqArchiveCrawler
{
    private const BROWSER_UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36';

    /**
     * @return list<string>
     */
    public function discoverPdfUrls(?string $exam, string $question): array
    {
        $examKey = $this->normalizeExam($exam, $question);
        if ($examKey === null) {
            return [];
        }

        $targetYear = (string) (now()->year - 1);
        $found = [];

        foreach ($this->seedPages($examKey) as $pageUrl) {
            foreach ($this->scrapePdfLinks($pageUrl) as $pdfUrl) {
                $found[$pdfUrl] = $this->scorePdfUrl($pdfUrl, $targetYear, $examKey);
            }
        }

        arsort($found);

        return array_keys(array_slice($found, 0, 5, true));
    }

    /**
     * @return list<string>
     */
    public function scrapePdfLinks(string $pageUrl): array
    {
        if (PyqSourceFilter::isBlockedUrl($pageUrl)) {
            return [];
        }

        $lower = mb_strtolower($pageUrl);
        if (str_contains($lower, '.pdf')) {
            return [$pageUrl];
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'User-Agent' => self::BROWSER_UA,
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                ])
                ->get($pageUrl);

            if (! $response->successful()) {
                return [];
            }

            $html = $response->body();
            if (! is_string($html) || $html === '') {
                return [];
            }

            return $this->extractPdfHrefs($html, $pageUrl);
        } catch (\Throwable $e) {
            Log::warning('OfficialPyqArchiveCrawler: page fetch failed', [
                'url' => $pageUrl,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * @return list<string>
     */
    protected function extractPdfHrefs(string $html, string $baseUrl): array
    {
        $base = $this->resolveBaseUrl($baseUrl);
        $urls = [];

        $patterns = [
            '/href=["\']([^"\']+\.pdf[^"\']*)["\']/i',
            '/href=["\']([^"\']*(?:question[_-]?paper|qp|pyq|neet|jee|upsc)[^"\']*\.pdf)["\']/i',
            '/href=["\']([^"\']+\/download\/[^"\']+)["\']/i',
            '/(?:data-url|data-href)=["\']([^"\']+\.pdf[^"\']*)["\']/i',
        ];

        foreach ($patterns as $pattern) {
            if (! preg_match_all($pattern, $html, $matches)) {
                continue;
            }

            foreach ($matches[1] ?? [] as $href) {
                $absolute = $this->absoluteUrl(html_entity_decode(trim($href)), $base);
                if ($absolute !== '' && ! PyqSourceFilter::isBlockedUrl($absolute)) {
                    $urls[] = $absolute;
                }
            }
        }

        return array_values(array_unique($urls));
    }

    protected function absoluteUrl(string $href, string $base): string
    {
        if ($href === '') {
            return '';
        }

        if (str_starts_with($href, 'http://') || str_starts_with($href, 'https://')) {
            return $href;
        }

        if (str_starts_with($href, '//')) {
            return 'https:' . $href;
        }

        if (str_starts_with($href, '/')) {
            return $base . $href;
        }

        return rtrim($base, '/') . '/' . ltrim($href, '/');
    }

    protected function resolveBaseUrl(string $url): string
    {
        $scheme = parse_url($url, PHP_URL_SCHEME) ?: 'https';
        $host = parse_url($url, PHP_URL_HOST) ?: '';

        return $scheme . '://' . $host;
    }

    protected function normalizeExam(?string $exam, string $question): ?string
    {
        $haystack = mb_strtolower(trim(($exam ?? '') . ' ' . $question));

        foreach ([
            'neet' => 'NEET',
            'jee' => 'JEE',
            'upsc' => 'UPSC',
            'cbse' => 'CBSE',
            'icse' => 'ICSE',
            'nta' => 'NEET',
        ] as $needle => $label) {
            if (str_contains($haystack, $needle)) {
                return $label;
            }
        }

        if (preg_match('/\b(?:class\s*)?10\s*(?:th)?\b/i', $haystack)) {
            return 'CBSE';
        }

        return null;
    }

    /**
     * @return list<string>
     */
    protected function seedPages(string $examKey): array
    {
        return match ($examKey) {
            'NEET' => [
                'https://neet.nta.nic.in/',
                'https://nta.ac.in/Examination/Previous-Question-Papers-Archives',
                'https://nta.ac.in/Examination',
            ],
            'JEE' => [
                'https://jeemain.nta.ac.in/',
                'https://nta.ac.in/Examination/Previous-Question-Papers-Archives',
            ],
            'UPSC' => [
                'https://upsc.gov.in/examinations/previous-question-papers',
                'https://upsc.gov.in/examinations/previous-question-papers-1',
            ],
            'CBSE' => [
                'https://cbse.gov.in/cbsenew/question-paper.html',
                'https://cbseacademic.nic.in/web_material/SQP/ClassX_2024-25/',
                'https://cbseacademic.nic.in/SQP.html',
            ],
            default => [],
        };
    }

    protected function scorePdfUrl(string $url, string $targetYear, string $examKey): int
    {
        $lower = mb_strtolower($url);
        $score = 0;

        if (str_contains($lower, '.pdf')) {
            $score += 5;
        }

        if (str_contains($lower, $targetYear)) {
            $score += 20;
        }

        if (str_contains($lower, mb_strtolower($examKey))) {
            $score += 10;
        }

        if ($examKey === 'CBSE' && (str_contains($lower, 'classx') || str_contains($lower, 'class-x') || str_contains($lower, 'class_10'))) {
            $score += 15;
        }

        if (PyqSourceFilter::isOfficialUrl($url)) {
            $score += 15;
        }

        if (str_contains($lower, 'question') || str_contains($lower, 'qp') || str_contains($lower, 'paper')) {
            $score += 5;
        }

        return $score;
    }
}
