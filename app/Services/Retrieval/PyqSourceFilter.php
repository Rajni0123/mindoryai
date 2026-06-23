<?php

namespace App\Services\Retrieval;

/**
 * Filters coaching-aggregator junk from PYQ / exam-paper retrieval.
 */
class PyqSourceFilter
{
    /**
     * @return list<string>
     */
    public static function blockedDomains(): array
    {
        $fromEnv = config('retrieval.pyq_blocked_domains', []);

        return is_array($fromEnv) ? array_values($fromEnv) : [];
    }

    /**
     * @return list<string>
     */
    public static function officialDomains(): array
    {
        $domains = config('retrieval.quiz_search.official_domains', []);

        return is_array($domains) ? array_values($domains) : [];
    }

    public static function isBlockedUrl(string $url): bool
    {
        $host = mb_strtolower((string) parse_url($url, PHP_URL_HOST));

        if ($host === '') {
            return false;
        }

        foreach (self::blockedDomains() as $blocked) {
            $blocked = mb_strtolower(trim($blocked));
            if ($blocked === '') {
                continue;
            }

            if ($host === $blocked || str_ends_with($host, '.' . $blocked)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Broad web PYQ fallback — allow coaching/university PDFs; block only non-PDF listing spam.
     */
    public static function isAllowedPyqPdfUrl(string $url): bool
    {
        $lower = mb_strtolower($url);

        if (! str_contains($lower, '.pdf')) {
            return false;
        }

        foreach (['bit.ly', 'tinyurl.com', 'adf.ly', 'malware'] as $spam) {
            if (str_contains($lower, $spam)) {
                return false;
            }
        }

        return true;
    }

    public static function isOfficialUrl(string $url): bool
    {
        $host = mb_strtolower((string) parse_url($url, PHP_URL_HOST));

        if ($host === '') {
            return false;
        }

        foreach (self::officialDomains() as $official) {
            $official = mb_strtolower(trim($official));
            if ($official === '') {
                continue;
            }

            if ($host === $official || str_ends_with($host, '.' . $official)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $sources
     * @return list<string>
     */
    public static function filterSourceLabels(array $sources): array
    {
        return array_values(array_filter($sources, function (string $source) {
            if (preg_match('/\((https?:\/\/[^)]+)\)/', $source, $matches)) {
                return ! self::isBlockedUrl($matches[1]);
            }

            return true;
        }));
    }

    /**
     * For PYQ replies — only keep government / NTA URLs in the sources list shown to AI.
     *
     * @param  list<string>  $sources
     * @return list<string>
     */
    public static function filterOfficialSourceLabels(array $sources): array
    {
        $official = array_values(array_filter($sources, function (string $source) {
            if (preg_match('/\((https?:\/\/[^)]+)\)/', $source, $matches)) {
                return self::isOfficialUrl($matches[1]);
            }

            return false;
        }));

        return $official !== [] ? $official : self::filterSourceLabels($sources);
    }
}
