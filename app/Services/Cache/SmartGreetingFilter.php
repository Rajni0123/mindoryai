<?php

namespace App\Services\Cache;

use Illuminate\Support\Facades\Cache;
use App\Models\GreetingPattern;

/**
 * LAYER 1: Smart Greeting Filter
 * Detects greetings/fillers that should NEVER be cached
 */
class SmartGreetingFilter
{
    private TextNormalizer $normalizer;
    private array $patterns = [];
    private array $categories = [];

    public function __construct(TextNormalizer $normalizer)
    {
        $this->normalizer = $normalizer;
        $this->loadPatterns();
    }

    /**
     * Load patterns from database (cached for 1 hour)
     */
    private function loadPatterns(): void
    {
        $this->patterns = Cache::remember('greeting_patterns', 3600, function () {
            try {
                return GreetingPattern::where('is_active', true)
                    ->pluck('pattern')
                    ->toArray();
            } catch (\Exception $e) {
                return $this->getDefaultPatterns();
            }
        });

        $this->categories = Cache::remember('greeting_categories', 3600, function () {
            try {
                return GreetingPattern::where('is_active', true)
                    ->pluck('category', 'pattern')
                    ->toArray();
            } catch (\Exception $e) {
                return [];
            }
        });
    }

    /**
     * Default patterns if database is not available
     */
    private function getDefaultPatterns(): array
    {
        return [
            // Greetings
            'hi', 'hello', 'hey', 'hii', 'hiii', 'helloo', 'hellooo',
            'namaste', 'namaskar', 'good morning', 'good afternoon',
            'good evening', 'good night', 'sup', 'wassup', 'yo',

            // Farewells
            'bye', 'goodbye', 'alvida', 'tata', 'see you', 'later',

            // Thanks
            'thanks', 'thank you', 'thanku', 'thnx', 'thx', 'ty',
            'dhanyawad', 'shukriya', 'bahut shukriya',

            // Fillers
            'ok', 'okay', 'okk', 'okkk', 'k', 'kk',
            'hmm', 'hmmm', 'hmmmm', 'umm', 'ummm',
            'accha', 'achha', 'acha', 'theek', 'theek hai', 'thik hai',
            'nice', 'great', 'good', 'awesome', 'cool', 'wow',
            'sahi', 'badhiya', 'mast', 'zabardast',

            // Meta questions
            'who are you', 'tum kaun ho', 'aap kaun ho',
            'help', 'help me', 'madad', 'madad karo',
            'what can you do', 'kya kar sakte ho',
        ];
    }

    /**
     * Check if message should skip cache
     * Returns: ['skip' => bool, 'reason' => string, 'category' => string]
     */
    public function shouldSkipCache(string $message): array
    {
        $config = config('smartcache.greeting_filter', []);

        if (!($config['enabled'] ?? true)) {
            return ['skip' => false, 'reason' => 'filter_disabled', 'category' => null];
        }

        // Clean and prepare message
        $cleaned = mb_strtolower(trim($message), 'UTF-8');

        // Collapse repeated characters (hiiiii -> hi)
        if ($config['collapse_repeated_chars'] ?? true) {
            $cleaned = $this->normalizer->collapseRepeatedChars($cleaned);
        }

        // Remove punctuation for matching
        $cleanedNoPunct = preg_replace('/[^\p{L}\p{N}\s]/u', '', $cleaned);
        $cleanedNoPunct = preg_replace('/\s+/', ' ', trim($cleanedNoPunct));

        $wordCount = $this->normalizer->wordCount($message);
        $maxWords = $config['max_word_count'] ?? 5;

        // Rule 1: If alphabetic content < 3 chars → SKIP
        $alphaOnly = preg_replace('/[^a-zA-Z\x{0900}-\x{097F}]/u', '', $cleanedNoPunct);
        if (mb_strlen($alphaOnly) < 3) {
            return ['skip' => true, 'reason' => 'too_short', 'category' => 'invalid'];
        }

        // Rule 2: If word_count ≤ 2 and matches pattern → SKIP
        if ($wordCount <= 2) {
            foreach ($this->patterns as $pattern) {
                $patternLower = mb_strtolower($pattern);
                if ($cleanedNoPunct === $patternLower) {
                    return [
                        'skip' => true,
                        'reason' => 'exact_match',
                        'category' => $this->categories[$pattern] ?? 'greeting'
                    ];
                }
            }
        }

        // Rule 3: If word_count ≤ 5 and starts with pattern → SKIP
        if ($wordCount <= $maxWords) {
            foreach ($this->patterns as $pattern) {
                $patternLower = mb_strtolower($pattern);
                if (str_starts_with($cleanedNoPunct, $patternLower . ' ') ||
                    str_starts_with($cleanedNoPunct, $patternLower)) {
                    // Make sure it's not a longer academic question starting with greeting
                    if (!$this->hasAcademicContent($cleanedNoPunct)) {
                        return [
                            'skip' => true,
                            'reason' => 'starts_with_pattern',
                            'category' => $this->categories[$pattern] ?? 'greeting'
                        ];
                    }
                }
            }
        }

        return ['skip' => false, 'reason' => 'passed', 'category' => null];
    }

    /**
     * Quick check if message has academic keywords
     */
    private function hasAcademicContent(string $text): bool
    {
        $academicEn = config('smartcache.academic_keywords_en', []);
        $academicHi = config('smartcache.academic_keywords_hi', []);
        $allAcademic = array_merge($academicEn, $academicHi);

        $textLower = mb_strtolower($text);
        foreach ($allAcademic as $keyword) {
            if (str_contains($textLower, mb_strtolower($keyword))) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get all active patterns
     */
    public function getPatterns(): array
    {
        return $this->patterns;
    }

    /**
     * Clear pattern cache
     */
    public function clearCache(): void
    {
        Cache::forget('greeting_patterns');
        Cache::forget('greeting_categories');
        $this->loadPatterns();
    }
}
