<?php

namespace App\Services\Cache;

class TextNormalizer
{
    private array $hinglishMap;
    private array $stopwordsEn;
    private array $stopwordsHi;

    public function __construct()
    {
        $this->hinglishMap = config('smartcache.hinglish_map', []);
        $this->stopwordsEn = config('smartcache.stopwords_en', []);
        $this->stopwordsHi = config('smartcache.stopwords_hi', []);
    }

    /**
     * Normalize text for caching and matching
     * Steps: lowercase → remove punctuation → hinglish mapping → remove stopwords → trim
     */
    public function normalize(string $text): string
    {
        $config = config('smartcache.normalization', []);

        // Step 1: Lowercase
        if ($config['lowercase'] ?? true) {
            $text = mb_strtolower($text, 'UTF-8');
        }

        // Step 2: Remove punctuation (keep Hindi Unicode characters)
        if ($config['remove_punctuation'] ?? true) {
            // Keep alphanumeric, Hindi characters (Devanagari range), and spaces
            $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
        }

        // Step 3: Collapse whitespace
        if ($config['collapse_whitespace'] ?? true) {
            $text = preg_replace('/\s+/', ' ', $text);
        }

        // Step 4: Hinglish to English mapping
        if ($config['hinglish_mapping'] ?? true) {
            $text = $this->applyHinglishMapping($text);
        }

        // Step 5: Remove stopwords
        if ($config['remove_stopwords'] ?? true) {
            $text = $this->removeStopwords($text);
        }

        return trim($text);
    }

    /**
     * Generate SHA256 hash for cache key
     */
    public function hash(string $text, ?string $context = null): string
    {
        $normalized = $this->normalize($text);
        $hashInput = $context ? "{$context}:{$normalized}" : $normalized;
        return hash('sha256', $hashInput);
    }

    /**
     * Apply Hinglish to English mapping
     */
    private function applyHinglishMapping(string $text): string
    {
        foreach ($this->hinglishMap as $hinglish => $english) {
            $text = str_ireplace($hinglish, $english, $text);
        }
        return $text;
    }

    /**
     * Remove stopwords from text
     */
    private function removeStopwords(string $text): string
    {
        $words = explode(' ', $text);
        $allStopwords = array_merge($this->stopwordsEn, $this->stopwordsHi);

        $filteredWords = array_filter($words, function ($word) use ($allStopwords) {
            return strlen($word) > 0 && !in_array(strtolower($word), $allStopwords);
        });

        return implode(' ', $filteredWords);
    }

    /**
     * Extract unique keywords from text (words > 2 chars after normalization)
     */
    public function extractKeywords(string $text): array
    {
        $normalized = $this->normalize($text);
        $words = explode(' ', $normalized);

        $keywords = [];
        foreach ($words as $word) {
            $word = trim($word);
            if (strlen($word) > 2) {
                $keywords[] = $word;
            }
        }

        return array_unique($keywords);
    }

    /**
     * Calculate Jaccard similarity between two texts
     * Returns value between 0 (no match) and 1 (exact match)
     */
    public function similarity(string $text1, string $text2): float
    {
        $keywords1 = $this->extractKeywords($text1);
        $keywords2 = $this->extractKeywords($text2);

        if (empty($keywords1) || empty($keywords2)) {
            return 0.0;
        }

        $intersection = array_intersect($keywords1, $keywords2);
        $union = array_unique(array_merge($keywords1, $keywords2));

        if (empty($union)) {
            return 0.0;
        }

        return count($intersection) / count($union);
    }

    /**
     * Detect language of text (en/hi/hinglish)
     */
    public function detectLanguage(string $text): string
    {
        // Check for Devanagari characters
        $hindiChars = preg_match_all('/[\x{0900}-\x{097F}]/u', $text);
        $englishChars = preg_match_all('/[a-zA-Z]/', $text);

        if ($hindiChars > 0 && $englishChars > 0) {
            return 'hinglish';
        } elseif ($hindiChars > $englishChars) {
            return 'hindi';
        }
        return 'english';
    }

    /**
     * Collapse repeated characters (hiiiii -> hi, okkk -> ok)
     */
    public function collapseRepeatedChars(string $text): string
    {
        // Collapse 3+ repeated chars to single char
        return preg_replace('/(.)\1{2,}/', '$1', $text);
    }

    /**
     * Get word count after cleaning
     */
    public function wordCount(string $text): int
    {
        $cleaned = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
        $cleaned = preg_replace('/\s+/', ' ', trim($cleaned));
        $words = array_filter(explode(' ', $cleaned), fn($w) => strlen($w) > 0);
        return count($words);
    }
}
