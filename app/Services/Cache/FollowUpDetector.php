<?php

namespace App\Services\Cache;

/**
 * LAYER 2: Follow-Up Detector (THE CRITICAL BUG FIX)
 *
 * This prevents the "Yes → Gandhi" bug where follow-up messages
 * get cached and return wrong answers.
 *
 * Follow-up messages ONLY make sense with previous conversation context.
 * They should NEVER be cached or matched from cache.
 */
class FollowUpDetector
{
    private TextNormalizer $normalizer;

    // Confirmation words - EXPANDED
    private array $confirmations = [
        // English
        'yes', 'yeah', 'yep', 'yup', 'ya', 'y',
        'ok', 'okay', 'okk', 'okkk', 'k', 'kk',
        'sure', 'alright', 'right', 'correct', 'exactly',
        'got it', 'understood', 'fine',
        // Hindi/Hinglish
        'haan', 'ha', 'haa', 'hnji', 'ji', 'ji ha', 'ji haan', 'jee',
        'sahi', 'sahi hai', 'theek', 'theek hai', 'thik hai',
        'bilkul', 'samjha', 'samjh gaya', 'samajh gaya',
    ];

    // Denial words - EXPANDED
    private array $denials = [
        // English
        'no', 'nope', 'nah', 'na', 'not',
        // Hindi
        'nahi', 'nhi', 'nahin', 'mat', 'galat',
    ];

    // Continue requests - EXPANDED
    // NOTE: 'samjhao' and 'batao' are NOT here because they can be valid question starters
    // when followed by a topic (e.g., "samjhao barish", "batao suraj")
    private array $continueRequests = [
        // English
        'continue', 'go on', 'go ahead', 'proceed', 'next',
        'more', 'tell me more', 'explain more', 'detail', 'details',
        'detailed', 'elaborate', 'expand', 'keep going', 'what next',
        'then what', 'and then', 'then',
        // Hindi/Hinglish - these are pure follow-ups (no topic expected after)
        'aage', 'aage batao', 'aur batao', 'aur sunao', 'phir', 'fir',
        'toh', 'aur', 'karo', 'bolo', 'phir kya',
        'detail me', 'detail me batao', 'vistar se',
        'aur samjhao', 'phir se', 'dobara',
    ];

    // Short reactions - EXPANDED
    private array $reactions = [
        // English
        'wow', 'nice', 'great', 'good', 'awesome', 'amazing',
        'cool', 'interesting', 'wonderful', 'fantastic', 'excellent',
        'perfect', 'brilliant',
        // Hindi/Hinglish
        'accha', 'achha', 'acha', 'sahi', 'badhiya', 'mast',
        'zabardast', 'kamaal', 'wah', 'waah', 'arre wah',
        // Thanks
        'thanks', 'thanku', 'thank you', 'thnx', 'thx', 'ty',
        'dhanyawad', 'shukriya', 'bahut shukriya',
        // Other short
        'hmm', 'hmmm', 'hmmmm', 'ohh', 'ohhh', 'oh', 'i see',
    ];

    // Numbered choices
    private array $numberedChoices = [
        '1', '2', '3', '4', '5',
        'first', 'second', 'third', 'fourth', 'fifth',
        'first one', 'second one', 'third one',
        'option 1', 'option 2', 'option 3',
        'pehla', 'pehla wala', 'doosra', 'doosra wala',
        'teesra', 'teesra wala', 'chautha',
    ];

    // Correction starters - EXPANDED
    private array $corrections = [
        // English corrections
        'no i meant', 'no not that', 'not that', 'not this',
        'i meant', 'actually', 'wait', 'hold on',
        'let me rephrase', 'let me reword', 'let me clarify',
        'wait let me', 'sorry i meant', 'i mean',
        'no wait', 'hang on', 'one sec', 'one second',
        // Hindi corrections
        'nahi wo nahi', 'wo nahi', 'maine kaha',
        'ruko', 'rukiye', 'ek minute', 'ek second',
        'matlab', 'mera matlab', 'galti se',
    ];

    // Pronouns that indicate context dependency - EXPANDED
    private array $contextPronouns = [
        // English pronouns
        'it', 'its', 'this', 'that', 'these', 'those',
        'them', 'their', 'theirs', 'him', 'her', 'hers',
        'he', 'she', 'they', 'we', 'us', 'our', 'ours',
        // Hindi pronouns
        'isko', 'usko', 'iska', 'uska', 'iski', 'uski',
        'yeh', 'ye', 'woh', 'wo', 'iske', 'uske',
        'inhe', 'unhe', 'inko', 'unko',
        'usne', 'jisne', 'jinhe', 'jisko', 'jinka',
        'isse', 'usse', 'ispe', 'uspe',
    ];

    public function __construct(TextNormalizer $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    /**
     * Check if message is a follow-up (should NEVER be cached)
     *
     * @param string $message The user's message
     * @param bool $isFirstMessage Whether this is the first message in conversation
     * @return bool True if it's a follow-up (skip cache), false otherwise
     */
    public function isFollowUp(string $message, bool $isFirstMessage = false): bool
    {
        // First messages are never follow-ups (obviously)
        // But we still check if they're valid academic questions
        $config = config('smartcache.followup_detection', []);
        if (!($config['enabled'] ?? true)) {
            return false;
        }

        // Clean message
        $cleaned = mb_strtolower(trim($message), 'UTF-8');
        $cleaned = $this->normalizer->collapseRepeatedChars($cleaned);
        $cleanedNoPunct = preg_replace('/[^\p{L}\p{N}\s]/u', '', $cleaned);
        $cleanedNoPunct = preg_replace('/\s+/', ' ', trim($cleanedNoPunct));

        $wordCount = $this->normalizer->wordCount($message);
        $charCount = mb_strlen($cleanedNoPunct);

        // Rule A: Exact match with any follow-up word → SKIP
        $allFollowUpWords = array_merge(
            $this->confirmations,
            $this->denials,
            $this->continueRequests,
            $this->reactions,
            $this->numberedChoices
        );

        foreach ($allFollowUpWords as $word) {
            if ($cleanedNoPunct === mb_strtolower($word)) {
                return true;
            }
        }

        // Rule B: 1-3 words matching any follow-up pattern → SKIP
        if ($wordCount <= 3) {
            foreach ($allFollowUpWords as $word) {
                $wordLower = mb_strtolower($word);
                if ($cleanedNoPunct === $wordLower ||
                    str_starts_with($cleanedNoPunct, $wordLower . ' ')) {
                    return true;
                }
            }

            // Check for corrections
            foreach ($this->corrections as $correction) {
                if (str_starts_with($cleanedNoPunct, mb_strtolower($correction))) {
                    return true;
                }
            }
        }

        // Rule C: Starts with follow-up word AND total words ≤ 5 → SKIP
        // EXCEPTION: Hindi verbs like "samjhao", "batao" followed by topic are valid questions
        if ($wordCount <= 5) {
            // First check if it's a valid Hindi question pattern (samjhao X, batao X)
            if ($this->isValidHindiQuestionPattern($cleanedNoPunct)) {
                // This is a valid question like "samjhao barish", not a follow-up
                // Skip this rule
            } else {
                $followUpStarters = array_merge(
                    $this->confirmations,
                    $this->denials,
                    $this->reactions,
                    $this->continueRequests
                );
                foreach ($followUpStarters as $word) {
                    $wordLower = mb_strtolower($word);
                    if (str_starts_with($cleanedNoPunct, $wordLower . ' ') ||
                        str_starts_with($cleanedNoPunct, $wordLower)) {
                        return true;
                    }
                }
            }

            // Also check corrections for ≤5 word messages
            foreach ($this->corrections as $correction) {
                if (str_starts_with($cleanedNoPunct, mb_strtolower($correction))) {
                    return true;
                }
            }
        }

        // Rule D: Contains context pronoun without academic noun → SKIP
        if ($config['check_pronouns'] ?? true) {
            if ($this->hasContextPronounWithoutAcademic($cleanedNoPunct)) {
                return true;
            }
        }

        // Rule E: NOT first message AND <= 20 chars → require explicit academic keyword
        // FIXED: Changed < to <= for consistency with other threshold checks
        $maxChars = $config['max_chars_without_keyword'] ?? 20;
        if (!$isFirstMessage && $charCount <= $maxChars) {
            if (!$this->hasAcademicKeyword($cleanedNoPunct)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if message has context pronouns without academic nouns
     */
    private function hasContextPronounWithoutAcademic(string $text): bool
    {
        $hasContextPronoun = false;
        foreach ($this->contextPronouns as $pronoun) {
            if (str_contains($text, mb_strtolower($pronoun))) {
                $hasContextPronoun = true;
                break;
            }
        }

        if (!$hasContextPronoun) {
            return false;
        }

        // If has context pronoun, check if it also has academic content
        return !$this->hasAcademicKeyword($text);
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
     * Check if message is a valid Hindi question pattern (not a follow-up)
     *
     * Examples:
     * - "samjhao barish" (explain rain) → valid question
     * - "batao suraj" (tell about sun) → valid question
     * - "samjhao" (just "explain") → follow-up (needs context)
     * - "batao isko" (tell about this) → follow-up (pronoun = needs context)
     */
    private function isValidHindiQuestionPattern(string $text): bool
    {
        // Hindi question starters that require a topic to be valid questions
        $hindiQuestionVerbs = [
            'samjhao', 'samjhaye', 'samjhaiye', 'samjha do',
            'batao', 'bataye', 'bataiye', 'bata do',
            'sikho', 'sikhao', 'sikhaiye',
            'padho', 'padhao', 'padhaiye',
        ];

        $textLower = mb_strtolower(trim($text));

        foreach ($hindiQuestionVerbs as $verb) {
            // Check if starts with this verb
            if (str_starts_with($textLower, $verb . ' ')) {
                // Get the part after the verb
                $afterVerb = trim(substr($textLower, strlen($verb)));

                // If nothing after verb, it's a follow-up ("samjhao" by itself)
                if (empty($afterVerb)) {
                    return false;
                }

                // If what follows is a pronoun, it's a follow-up ("samjhao isko")
                $pronounsAfterVerb = [
                    'isko', 'usko', 'isse', 'usse', 'ye', 'yeh', 'wo', 'woh',
                    'ispe', 'uspe', 'isme', 'usme', 'inka', 'unka',
                    'this', 'that', 'it', 'them',
                ];

                $firstWordAfterVerb = explode(' ', $afterVerb)[0];
                if (in_array($firstWordAfterVerb, $pronounsAfterVerb)) {
                    return false;
                }

                // Has a topic word after verb - valid question!
                return true;
            }

            // Also check exact match (verb alone = follow-up)
            if ($textLower === $verb) {
                return false;
            }
        }

        // Also handle "kya hai X" patterns - these are questions
        $kyaPatterns = [
            'kya hai ', 'kya hota hai ', 'kya hoti hai ', 'kya hote hai ',
            'kaun hai ', 'kaun tha ', 'kaun the ',
            'kyu hai ', 'kyu hota hai ', 'kaise hai ', 'kaise hota hai ',
        ];

        foreach ($kyaPatterns as $pattern) {
            if (str_starts_with($textLower, $pattern)) {
                $afterPattern = trim(substr($textLower, strlen($pattern)));
                if (!empty($afterPattern)) {
                    return true;
                }
            }
        }

        // Also handle "X kya hai" (topic followed by question)
        if (str_ends_with($textLower, ' kya hai') ||
            str_ends_with($textLower, ' kya hota hai') ||
            str_ends_with($textLower, ' kya hoti hai')) {
            // Has topic before "kya hai" - valid question
            return true;
        }

        return false;
    }

    /**
     * Get detailed analysis of why message is/isn't a follow-up
     */
    public function analyze(string $message, bool $isFirstMessage = false): array
    {
        $isFollowUp = $this->isFollowUp($message, $isFirstMessage);

        $cleaned = mb_strtolower(trim($message), 'UTF-8');
        $cleaned = $this->normalizer->collapseRepeatedChars($cleaned);
        $cleanedNoPunct = preg_replace('/[^\p{L}\p{N}\s]/u', '', $cleaned);
        $cleanedNoPunct = preg_replace('/\s+/', ' ', trim($cleanedNoPunct));

        return [
            'is_followup' => $isFollowUp,
            'original' => $message,
            'cleaned' => $cleanedNoPunct,
            'word_count' => $this->normalizer->wordCount($message),
            'char_count' => mb_strlen($cleanedNoPunct),
            'is_first_message' => $isFirstMessage,
            'has_academic_keyword' => $this->hasAcademicKeyword($cleanedNoPunct),
            'has_context_pronoun' => $this->hasContextPronounWithoutAcademic($cleanedNoPunct),
            'recommendation' => $isFollowUp ? 'SKIP_CACHE' : 'PROCEED',
        ];
    }
}
