<?php

namespace App\Services\Cache;

/**
 * LAYER 3: Message Quality Gate
 * Minimum quality standards for cacheability
 *
 * A message must meet ALL these criteria to be cached:
 * 1. Minimum 10 characters after cleaning
 * 2. Minimum 2 meaningful words after stopword removal
 * 3. Must contain at least 1 academic keyword
 * 4. Not purely numeric
 * 5. Has actual alphabetic content
 */
class MessageQualityGate
{
    private TextNormalizer $normalizer;

    public function __construct(TextNormalizer $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    /**
     * Check if message meets quality standards for caching
     * Returns: ['passes' => bool, 'reason' => string, 'details' => array]
     */
    public function meetsQuality(string $message): array
    {
        $config = config('smartcache.quality_gate', []);

        $minChars = $config['min_characters'] ?? 10;
        $minWords = $config['min_words'] ?? 2;
        $requireAcademic = $config['require_academic_keyword'] ?? true;

        // Clean the message
        $cleaned = mb_strtolower(trim($message), 'UTF-8');
        $cleanedNoPunct = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $cleaned);
        $cleanedNoPunct = preg_replace('/\s+/', ' ', trim($cleanedNoPunct));

        // Check 1: Not purely punctuation/special chars
        $alphaContent = preg_replace('/[^a-zA-Z\x{0900}-\x{097F}]/u', '', $cleanedNoPunct);
        if (mb_strlen($alphaContent) < 3) {
            return [
                'passes' => false,
                'reason' => 'insufficient_alphabetic_content',
                'details' => [
                    'alpha_chars' => mb_strlen($alphaContent),
                    'required' => 3,
                ]
            ];
        }

        // Check 2: Not purely numeric
        $numericOnly = preg_replace('/[^0-9]/', '', $cleanedNoPunct);
        $nonNumeric = preg_replace('/[0-9]/', '', $cleanedNoPunct);
        if (strlen($numericOnly) > 0 && mb_strlen(trim($nonNumeric)) < 3) {
            return [
                'passes' => false,
                'reason' => 'purely_numeric',
                'details' => [
                    'message' => 'Message is purely numeric or has minimal text',
                ]
            ];
        }

        // Check 3: Minimum characters after cleaning
        // EXCEPTION: If message has academic keyword, lower the threshold
        $bypassIfAcademic = $config['bypass_char_limit_if_academic'] ?? true;
        $hasAcademicEarly = $this->hasAcademicKeyword($cleanedNoPunct);

        if (mb_strlen($cleanedNoPunct) < $minChars) {
            // If academic keyword present and bypass enabled, use lower threshold
            if ($bypassIfAcademic && $hasAcademicEarly && mb_strlen($cleanedNoPunct) >= 5) {
                // Allow short academic questions (min 5 chars like "DNA")
                // Continue to other checks
            } else {
                return [
                    'passes' => false,
                    'reason' => 'too_short',
                    'details' => [
                        'chars' => mb_strlen($cleanedNoPunct),
                        'required' => $minChars,
                    ]
                ];
            }
        }

        // Check 4: Minimum meaningful words after normalization
        $normalized = $this->normalizer->normalize($message);
        $words = array_filter(explode(' ', $normalized), fn($w) => mb_strlen($w) > 1);
        $wordCount = count($words);

        if ($wordCount < $minWords) {
            return [
                'passes' => false,
                'reason' => 'too_few_words',
                'details' => [
                    'words' => $wordCount,
                    'required' => $minWords,
                    'words_found' => $words,
                ]
            ];
        }

        // Check 5: Must contain academic keyword (if required)
        if ($requireAcademic) {
            $hasAcademic = $this->hasAcademicKeyword($cleanedNoPunct);
            if (!$hasAcademic) {
                return [
                    'passes' => false,
                    'reason' => 'no_academic_keyword',
                    'details' => [
                        'message' => 'No academic keyword found in message',
                    ]
                ];
            }
        }

        // All checks passed
        return [
            'passes' => true,
            'reason' => 'all_checks_passed',
            'details' => [
                'chars' => mb_strlen($cleanedNoPunct),
                'words' => $wordCount,
                'has_academic' => true,
            ]
        ];
    }

    /**
     * Quick check - just returns bool
     */
    public function passes(string $message): bool
    {
        return $this->meetsQuality($message)['passes'];
    }

    /**
     * Check if message has at least one academic keyword
     */
    private function hasAcademicKeyword(string $text): bool
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
     * Get all failed checks for debugging
     */
    public function getFailedChecks(string $message): array
    {
        $result = $this->meetsQuality($message);
        if ($result['passes']) {
            return [];
        }

        return [
            'reason' => $result['reason'],
            'details' => $result['details'],
        ];
    }
}
