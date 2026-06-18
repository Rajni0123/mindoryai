<?php

namespace App\Services\Cache;

use App\Models\AnswerBank;
use App\Models\FormulaBank;
use App\Models\NcertCache;
use App\Models\VideoCache;
use App\Models\ImageCache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Smart Cache Service - Main Orchestrator
 *
 * The brain that runs all 5 layers:
 * Layer 1: SmartGreetingFilter
 * Layer 2: FollowUpDetector
 * Layer 3: MessageQualityGate
 * Layer 4: Feature type check
 * Layer 5: Context dependency (pronoun check)
 */
class SmartCacheService
{
    private TextNormalizer $normalizer;
    private SmartGreetingFilter $greetingFilter;
    private FollowUpDetector $followUpDetector;
    private MessageQualityGate $qualityGate;
    private CacheAnalyticsService $analytics;

    public function __construct(
        TextNormalizer $normalizer,
        SmartGreetingFilter $greetingFilter,
        FollowUpDetector $followUpDetector,
        MessageQualityGate $qualityGate,
        CacheAnalyticsService $analytics
    ) {
        $this->normalizer = $normalizer;
        $this->greetingFilter = $greetingFilter;
        $this->followUpDetector = $followUpDetector;
        $this->qualityGate = $qualityGate;
        $this->analytics = $analytics;
    }

    /**
     * Main lookup method - runs all 5 layers
     *
     * @param string $question The user's question
     * @param string $sourceType Feature type (ai_doubt, scan_solve, etc.)
     * @param string|null $subject Subject filter
     * @param string|null $examType Exam type filter
     * @param bool $isFirstMessage Whether this is the first message in conversation
     * @return array ['hit' => bool, 'answer' => string|null, 'match_level' => string, 'reason' => string]
     */
    public function lookup(
        string $question,
        string $sourceType = 'ai_doubt',
        ?string $subject = null,
        ?string $examType = null,
        bool $isFirstMessage = false
    ): array {
        $startTime = microtime(true);

        // SAFETY NET: Ultra-short messages should NEVER hit cache
        // This prevents "Yes -> Gandhi" type bugs from ever happening
        // EXCEPTION: First messages with academic keywords OR educational patterns can bypass
        $trimmed = trim($question);
        $minChars = config('smartcache.quality_gate.min_chars_for_cache_lookup', 15);
        if (mb_strlen($trimmed) <= $minChars) {
            // For first messages, check if it has academic keyword or educational pattern
            $hasAcademic = $this->hasAcademicKeyword($trimmed);
            $hasEducational = $this->hasEducationalQuestionPattern($trimmed);

            if ($isFirstMessage && ($hasAcademic || $hasEducational)) {
                Log::debug("[SmartCache] Safety net bypassed - first message with academic/educational content", [
                    'chars' => mb_strlen($trimmed),
                    'question' => $trimmed,
                    'has_academic' => $hasAcademic,
                    'has_educational' => $hasEducational,
                ]);
                // Continue to next checks instead of blocking
            } else {
                $this->analytics->recordRequest($sourceType, false, 'too_short_safety');
                Log::debug("[SmartCache] SAFETY NET - message too short", [
                    'chars' => mb_strlen($trimmed),
                    'min' => $minChars
                ]);
                return $this->miss('too_short_safety', 'Message too short for cache lookup');
            }
        }

        // Layer 4: Check if feature has cache enabled
        if (!$this->isFeatureCacheable($sourceType)) {
            $this->analytics->recordRequest($sourceType, false, 'feature_disabled');
            return $this->miss('feature_disabled', "Feature '$sourceType' has caching disabled");
        }

        // Layer 1: Greeting Filter
        $greetingResult = $this->greetingFilter->shouldSkipCache($question);
        if ($greetingResult['skip']) {
            $this->analytics->recordRequest($sourceType, false, 'greeting_filtered');
            return $this->miss('greeting_filtered', $greetingResult['reason']);
        }

        // Layer 2: Follow-Up Detector (THE CRITICAL BUG FIX)
        if ($this->followUpDetector->isFollowUp($question, $isFirstMessage)) {
            $this->analytics->recordRequest($sourceType, false, 'followup_detected');
            return $this->miss('followup_detected', 'Message is a follow-up to previous conversation');
        }

        // Layer 3: Quality Gate
        $qualityResult = $this->qualityGate->meetsQuality($question);
        if (!$qualityResult['passes']) {
            $this->analytics->recordRequest($sourceType, false, 'quality_failed');
            return $this->miss('quality_failed', $qualityResult['reason']);
        }

        // All filters passed - proceed to cache lookup
        $hash = $this->normalizer->hash($question, $sourceType);

        // Level 1: Exact hash match (fastest - 1ms target)
        $exactMatch = $this->lookupExactHash($hash, $sourceType);
        if ($exactMatch) {
            $this->recordHit($exactMatch, $sourceType);
            $elapsed = round((microtime(true) - $startTime) * 1000, 2);
            Log::info("[SmartCache] HIT (exact) in {$elapsed}ms", ['hash' => substr($hash, 0, 8)]);

            return $this->hit($exactMatch->answer, 'exact_hash', $exactMatch->id);
        }

        // Level 2: FULLTEXT search (5ms target)
        $fulltextMatch = $this->lookupFulltext($question, $sourceType, $subject, $examType);
        if ($fulltextMatch) {
            $this->recordHit($fulltextMatch, $sourceType);
            $elapsed = round((microtime(true) - $startTime) * 1000, 2);
            Log::info("[SmartCache] HIT (fulltext) in {$elapsed}ms", ['id' => $fulltextMatch->id]);

            return $this->hit($fulltextMatch->answer, 'fulltext', $fulltextMatch->id);
        }

        // Level 3: Keyword similarity search
        $keywordMatch = $this->lookupKeywordSimilarity($question, $sourceType, $subject, $examType);
        if ($keywordMatch) {
            $this->recordHit($keywordMatch['entry'], $sourceType);
            $elapsed = round((microtime(true) - $startTime) * 1000, 2);
            Log::info("[SmartCache] HIT (keyword) in {$elapsed}ms", [
                'id' => $keywordMatch['entry']->id,
                'similarity' => $keywordMatch['similarity']
            ]);

            return $this->hit($keywordMatch['entry']->answer, 'keyword_similarity', $keywordMatch['entry']->id);
        }

        // All levels missed
        $this->analytics->recordRequest($sourceType, false, 'no_match');
        $elapsed = round((microtime(true) - $startTime) * 1000, 2);
        Log::info("[SmartCache] MISS in {$elapsed}ms", ['question' => substr($question, 0, 50)]);

        return $this->miss('no_match', 'No matching entry found in cache');
    }

    /**
     * Store a response in cache
     *
     * CRITICAL: First check all filters - if they say skip, DO NOT STORE
     */
    public function store(
        string $question,
        string $answer,
        string $sourceType = 'ai_doubt',
        ?string $subject = null,
        ?string $topic = null,
        ?string $classLevel = null,
        ?string $examType = null,
        int $tokenCount = 0
    ): bool {
        // GOLDEN RULE: Check all filters BEFORE storing
        // Never store responses to follow-ups or greetings

        // Check feature
        if (!$this->isFeatureCacheable($sourceType)) {
            Log::debug("[SmartCache] SKIP STORE - feature disabled", ['type' => $sourceType]);
            return false;
        }

        // Check greeting filter
        $greetingResult = $this->greetingFilter->shouldSkipCache($question);
        if ($greetingResult['skip']) {
            Log::debug("[SmartCache] SKIP STORE - greeting", ['reason' => $greetingResult['reason']]);
            return false;
        }

        // Check follow-up detector
        if ($this->followUpDetector->isFollowUp($question, false)) {
            Log::debug("[SmartCache] SKIP STORE - followup detected");
            return false;
        }

        // Check quality gate
        if (!$this->qualityGate->passes($question)) {
            Log::debug("[SmartCache] SKIP STORE - quality failed");
            return false;
        }

        // Don't store very short answers (likely greetings/reactions)
        if (mb_strlen(trim($answer)) < 50) {
            Log::debug("[SmartCache] SKIP STORE - answer too short", ['length' => mb_strlen(trim($answer))]);
            return false;
        }

        // CRITICAL: Don't store context-dependent responses
        // These are responses that reference previous conversation
        if ($this->isContextDependentResponse($answer)) {
            Log::debug("[SmartCache] SKIP STORE - context-dependent response detected");
            return false;
        }

        // CRITICAL: Don't store error/invalid responses
        // These are API errors, timeouts, or "I don't understand" responses
        if ($this->isErrorResponse($answer)) {
            Log::debug("[SmartCache] SKIP STORE - error/invalid response detected");
            return false;
        }

        // Don't store if question is generic
        $genericPatterns = [
            '/^(what|how|why|when|where|who)\s+(is|are|was|were)\s*$/i',
            '/^(kya|kaise|kyu|kab|kahan)\s*$/i',
        ];
        foreach ($genericPatterns as $pattern) {
            if (preg_match($pattern, trim($question))) {
                Log::debug("[SmartCache] SKIP STORE - generic question pattern");
                return false;
            }
        }

        // All checks passed - store in cache
        try {
            $hash = $this->normalizer->hash($question, $sourceType);
            $normalized = $this->normalizer->normalize($question);
            $language = $this->normalizer->detectLanguage($question);
            $costSaved = config("smartcache.costs.gemini_flash", 0.12);

            // Get TTL from config
            $ttlDays = config("smartcache.cacheable_features.{$sourceType}.ttl_days", 90);
            $expiresAt = now()->addDays($ttlDays);

            // Use transaction with locking to prevent race conditions
            DB::transaction(function () use ($hash, $sourceType, $question, $normalized, $answer, $subject, $topic, $classLevel, $examType, $language, $tokenCount, $costSaved, $expiresAt) {
                // Try to get existing entry with lock
                $existing = AnswerBank::where('question_hash', $hash)
                    ->where('source_type', $sourceType)
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    // Update existing entry
                    $existing->update([
                        'original_question' => $question,
                        'normalized_question' => $normalized,
                        'answer' => $answer,
                        'subject' => $subject,
                        'topic' => $topic,
                        'class_level' => $classLevel,
                        'exam_type' => $examType,
                        'language' => $language,
                        'token_count_saved' => $tokenCount,
                        'cost_saved' => $costSaved,
                        'is_active' => true,
                        'expires_at' => $expiresAt,
                    ]);
                } else {
                    // Create new entry
                    AnswerBank::create([
                        'question_hash' => $hash,
                        'source_type' => $sourceType,
                        'original_question' => $question,
                        'normalized_question' => $normalized,
                        'answer' => $answer,
                        'subject' => $subject,
                        'topic' => $topic,
                        'class_level' => $classLevel,
                        'exam_type' => $examType,
                        'language' => $language,
                        'token_count_saved' => $tokenCount,
                        'cost_saved' => $costSaved,
                        'is_active' => true,
                        'expires_at' => $expiresAt,
                    ]);
                }
            }, 3); // Retry up to 3 times on deadlock

            Log::info("[SmartCache] STORED", [
                'hash' => substr($hash, 0, 8),
                'type' => $sourceType,
                'question' => substr($question, 0, 50),
                'expires_at' => $expiresAt->toDateString(),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("[SmartCache] STORE ERROR", ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Check if response is context-dependent (references previous conversation)
     * Such responses should NEVER be cached as they'll return wrong answers
     */
    private function isContextDependentResponse(string $answer): bool
    {
        $answerLower = mb_strtolower($answer, 'UTF-8');

        // Phrases that indicate the response is context-dependent
        $contextPhrases = [
            'continue from where',
            'where we left off',
            'as i mentioned',
            'as we discussed',
            'let me continue',
            'continuing from',
            'back to our',
            'as i was saying',
            'as mentioned earlier',
            'as i explained',
            'going back to',
            'returning to our',
            'picking up from',
            'as you asked earlier',
            'to answer your previous',
            'regarding your earlier',
            'from our previous',
            'in our last conversation',
            // Hindi/Hinglish context phrases
            'jaise maine bataya',
            'jaise humne discuss kiya',
            'pichle sawaal',
            'aage badhte hain',
            'jahan se chhoda tha',
        ];

        foreach ($contextPhrases as $phrase) {
            if (str_contains($answerLower, $phrase)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if response is an error/invalid response
     * Such responses should NEVER be cached
     */
    private function isErrorResponse(string $answer): bool
    {
        $answerLower = mb_strtolower($answer, 'UTF-8');

        // Error/invalid response patterns
        $errorPatterns = [
            'i am sorry',
            'i cannot understand',
            'i don\'t understand',
            'i dont understand',
            "don't understand",
            'dont understand',
            'error occurred',
            'unable to process',
            'please try again',
            'something went wrong',
            'api error',
            'rate limit',
            'timeout',
            'service unavailable',
            'invalid request',
            'please rephrase',
            'could not process',
            'failed to generate',
            'technical difficulty',
            'server error',
            // Hindi error responses
            'maaf kijiye',
            'samajh nahi aaya',
            'dobara try',
            'kuch galat',
        ];

        foreach ($errorPatterns as $pattern) {
            if (str_contains($answerLower, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if message has academic keywords (for safety net bypass)
     */
    private function hasAcademicKeyword(string $text): bool
    {
        $text = mb_strtolower($text, 'UTF-8');

        $academicEn = config('smartcache.academic_keywords_en', []);
        $academicHi = config('smartcache.academic_keywords_hi', []);
        $allAcademic = array_merge($academicEn, $academicHi);

        foreach ($allAcademic as $keyword) {
            if (str_contains($text, mb_strtolower($keyword))) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if message starts with educational question pattern
     * "what is X", "explain Y", "define Z" are clearly educational
     */
    private function hasEducationalQuestionPattern(string $text): bool
    {
        $textLower = mb_strtolower(trim($text), 'UTF-8');

        // Educational question patterns
        $patterns = [
            'what is ', 'what are ', 'what was ', 'what were ',
            'who is ', 'who are ', 'who was ',
            'why is ', 'why are ', 'why do ', 'why does ',
            'how is ', 'how are ', 'how do ', 'how does ', 'how to ',
            'when is ', 'when was ', 'where is ', 'where are ',
            'explain ', 'define ', 'describe ', 'meaning of ', 'definition of ',
            // Hindi/Hinglish
            'kya hai ', 'kya hota ', 'kya hoti ', 'kaun hai ',
            'kyu hai ', 'kaise hai ', 'kaise hota ', 'kab hai ',
            'samjhao ', 'batao ', 'bataiye ',
        ];

        foreach ($patterns as $pattern) {
            if (str_starts_with($textLower, $pattern)) {
                return true;
            }
        }

        // Check for Hindi question endings
        if (str_ends_with($textLower, ' kya hai') ||
            str_ends_with($textLower, ' kya hota hai')) {
            return true;
        }

        return false;
    }

    /**
     * Check if feature has caching enabled
     */
    private function isFeatureCacheable(string $sourceType): bool
    {
        $neverCache = config('smartcache.never_cache', []);
        if (in_array($sourceType, $neverCache)) {
            return false;
        }

        $cacheableFeatures = config('smartcache.cacheable_features', []);
        return isset($cacheableFeatures[$sourceType]) &&
               ($cacheableFeatures[$sourceType]['enabled'] ?? false);
    }

    /**
     * Level 1: Exact hash lookup with TTL enforcement
     */
    private function lookupExactHash(string $hash, string $sourceType): ?AnswerBank
    {
        return AnswerBank::where('question_hash', $hash)
            ->where('source_type', $sourceType)
            ->where('is_active', true)
            ->where(function ($query) {
                // TTL enforcement: check expires_at if set, or fallback to created_at
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();
    }

    /**
     * Level 2: FULLTEXT search
     * With LENGTH-AWARE relevance thresholds to prevent false matches
     */
    private function lookupFulltext(
        string $question,
        string $sourceType,
        ?string $subject,
        ?string $examType
    ): ?AnswerBank {
        $normalized = $this->normalizer->normalize($question);
        $wordCount = count(explode(' ', $normalized));
        $charCount = mb_strlen($normalized);

        // SHORT QUERY PROTECTION
        // Short questions = exact hash only, no fuzzy matching
        $minChars = config('smartcache.matching.min_chars_for_fuzzy', 20);
        $minWords = config('smartcache.matching.min_words_for_fuzzy', 4);

        if ($charCount < $minChars || $wordCount < $minWords) {
            Log::debug("[SmartCache] Skipping fulltext - too short", [
                'chars' => $charCount,
                'words' => $wordCount,
                'min_chars' => $minChars,
                'min_words' => $minWords
            ]);
            return null;
        }

        // DYNAMIC RELEVANCE THRESHOLD
        // Shorter questions need HIGHER relevance to match
        $baseRelevance = config('smartcache.matching.min_fulltext_relevance', 1.5);
        if ($wordCount <= 5) {
            $minRelevance = $baseRelevance * 2.0;
        } elseif ($wordCount <= 8) {
            $minRelevance = $baseRelevance * 1.5;
        } else {
            $minRelevance = $baseRelevance;
        }

        $query = AnswerBank::selectRaw("*, MATCH(original_question, normalized_question) AGAINST(? IN BOOLEAN MODE) as relevance", [$normalized])
            ->where('source_type', $sourceType)
            ->where('is_active', true)
            ->where(function ($q) {
                // TTL enforcement
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->whereRaw("MATCH(original_question, normalized_question) AGAINST(? IN BOOLEAN MODE)", [$normalized])
            ->having('relevance', '>', $minRelevance);

        if ($subject) {
            $query->where('subject', $subject);
        }
        if ($examType) {
            $query->where('exam_type', $examType);
        }

        return $query->orderBy('relevance', 'desc')
            ->orderBy('hit_count', 'desc')
            ->first();
    }

    /**
     * Level 3: Keyword similarity search
     * With LENGTH-AWARE similarity thresholds
     */
    private function lookupKeywordSimilarity(
        string $question,
        string $sourceType,
        ?string $subject,
        ?string $examType
    ): ?array {
        $keywords = $this->normalizer->extractKeywords($question);

        // Need at least 3 keywords for similarity matching
        if (count($keywords) < 3) {
            Log::debug("[SmartCache] Skipping keyword similarity - too few keywords", [
                'keyword_count' => count($keywords)
            ]);
            return null;
        }

        // Dynamic threshold - shorter = stricter
        $baseThreshold = config('smartcache.matching.similarity_threshold', 0.80);
        if (count($keywords) <= 4) {
            $threshold = 0.90; // Very strict for short queries
        } else {
            $threshold = $baseThreshold;
        }

        $maxCandidates = config('smartcache.matching.max_keyword_candidates', 10);

        // Build LIKE query for each keyword
        $query = AnswerBank::where('source_type', $sourceType)
            ->where('is_active', true)
            ->where(function ($q) {
                // TTL enforcement
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });

        if ($subject) {
            $query->where('subject', $subject);
        }
        if ($examType) {
            $query->where('exam_type', $examType);
        }

        // Add keyword conditions
        $query->where(function ($q) use ($keywords) {
            foreach ($keywords as $keyword) {
                $q->orWhere('normalized_question', 'LIKE', "%{$keyword}%");
            }
        });

        $candidates = $query->orderBy('hit_count', 'desc')
            ->limit($maxCandidates)
            ->get();

        // Calculate similarity for each candidate
        $bestMatch = null;
        $bestSimilarity = 0;

        foreach ($candidates as $candidate) {
            $similarity = $this->normalizer->similarity($question, $candidate->original_question);
            if ($similarity >= $threshold && $similarity > $bestSimilarity) {
                $bestMatch = $candidate;
                $bestSimilarity = $similarity;
            }
        }

        if ($bestMatch) {
            return [
                'entry' => $bestMatch,
                'similarity' => $bestSimilarity,
            ];
        }

        return null;
    }

    /**
     * Record a cache hit
     */
    private function recordHit(AnswerBank $entry, string $sourceType): void
    {
        $entry->increment('hit_count');
        $entry->update(['last_hit_at' => now()]);
        $this->analytics->recordRequest($sourceType, true, 'hit');
    }

    /**
     * Build hit response
     */
    private function hit(string $answer, string $matchLevel, int $entryId): array
    {
        return [
            'hit' => true,
            'answer' => $answer,
            'match_level' => $matchLevel,
            'entry_id' => $entryId,
            'reason' => 'cache_hit',
        ];
    }

    /**
     * Build miss response
     */
    private function miss(string $reason, string $message): array
    {
        return [
            'hit' => false,
            'answer' => null,
            'match_level' => null,
            'entry_id' => null,
            'reason' => $reason,
            'message' => $message,
        ];
    }

    // ========== FORMULA BANK ==========

    /**
     * Lookup formula in formula_bank
     */
    public function lookupFormula(string $query, ?string $subject = null): ?FormulaBank
    {
        $hash = $this->normalizer->hash($query, 'formula');

        // Exact hash match
        $exact = FormulaBank::where('content_hash', $hash)
            ->where('is_active', true)
            ->first();

        if ($exact) {
            $exact->increment('hit_count');
            return $exact;
        }

        // Fulltext search
        $normalized = $this->normalizer->normalize($query);
        $fulltextQuery = FormulaBank::selectRaw("*, MATCH(name, keywords) AGAINST(? IN BOOLEAN MODE) as relevance", [$normalized])
            ->where('is_active', true)
            ->whereRaw("MATCH(name, keywords) AGAINST(? IN BOOLEAN MODE)", [$normalized]);

        if ($subject) {
            $fulltextQuery->where('subject', $subject);
        }

        $result = $fulltextQuery->orderBy('relevance', 'desc')
            ->orderBy('hit_count', 'desc')
            ->first();

        if ($result) {
            $result->increment('hit_count');
        }

        return $result;
    }

    // ========== NCERT CACHE ==========

    /**
     * Lookup NCERT content
     */
    public function lookupNcert(string $query, ?string $subject = null, ?string $classLevel = null): ?NcertCache
    {
        $normalized = $this->normalizer->normalize($query);

        $fulltextQuery = NcertCache::selectRaw("*, MATCH(chapter_name, content) AGAINST(? IN BOOLEAN MODE) as relevance", [$normalized])
            ->where('is_active', true)
            ->whereRaw("MATCH(chapter_name, content) AGAINST(? IN BOOLEAN MODE)", [$normalized]);

        if ($subject) {
            $fulltextQuery->where('subject', $subject);
        }
        if ($classLevel) {
            $fulltextQuery->where('class_level', $classLevel);
        }

        $result = $fulltextQuery->orderBy('relevance', 'desc')
            ->orderBy('hit_count', 'desc')
            ->first();

        if ($result) {
            $result->increment('hit_count');
        }

        return $result;
    }

    // ========== VIDEO CACHE ==========

    public function lookupVideo(string $topic, ?string $style = null, ?int $duration = null): ?VideoCache
    {
        $hash = $this->normalizer->hash($topic . ($style ?? '') . ($duration ?? ''), 'video');

        return VideoCache::where('content_hash', $hash)
            ->where('is_active', true)
            ->first();
    }

    public function storeVideo(string $topic, array $storyboard, string $script, ?string $style = null, ?int $duration = null): bool
    {
        try {
            $hash = $this->normalizer->hash($topic . ($style ?? '') . ($duration ?? ''), 'video');

            VideoCache::updateOrCreate(
                ['content_hash' => $hash],
                [
                    'topic' => $topic,
                    'style' => $style,
                    'duration_seconds' => $duration,
                    'storyboard_json' => json_encode($storyboard),
                    'script_text' => $script,
                    'is_active' => true,
                ]
            );
            return true;
        } catch (\Exception $e) {
            Log::error("[SmartCache] Video store error", ['error' => $e->getMessage()]);
            return false;
        }
    }

    // ========== IMAGE CACHE ==========

    public function lookupImage(string $topic, ?string $style = null, ?int $frameNumber = null): ?ImageCache
    {
        $hash = $this->normalizer->hash($topic . ($style ?? '') . ($frameNumber ?? ''), 'image');

        return ImageCache::where('image_hash', $hash)
            ->where('is_active', true)
            ->first();
    }

    public function storeImage(string $topic, string $filePath, int $fileSize, ?string $style = null, ?int $frameNumber = null): bool
    {
        try {
            $hash = $this->normalizer->hash($topic . ($style ?? '') . ($frameNumber ?? ''), 'image');

            ImageCache::updateOrCreate(
                ['image_hash' => $hash],
                [
                    'topic' => $topic,
                    'style' => $style,
                    'frame_number' => $frameNumber,
                    'file_path' => $filePath,
                    'file_size' => $fileSize,
                    'is_active' => true,
                ]
            );
            return true;
        } catch (\Exception $e) {
            Log::error("[SmartCache] Image store error", ['error' => $e->getMessage()]);
            return false;
        }
    }
}
