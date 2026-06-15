<?php

namespace App\Services\Cache;

class MessageClassifier
{
    // Minimum message length to consider for standalone cache lookup
    // Messages shorter than this are NEVER cached as standalone
    private const MIN_CACHEABLE_LENGTH = 20;

    // Continuation words — NEVER cache lookup (ONLY exact matches)
    // NOTE: Do NOT include question words like 'what', 'why', 'how' here
    // because they can start legitimate questions like "What is photosynthesis"
    private array $continuations = [
        // English continuations (confirmation/continuation words only)
        'yes', 'yeah', 'yep', 'yup', 'ok', 'okay', 'sure',
        'no', 'nope', 'nah',
        'more', 'continue', 'next', 'further', 'details',
        'explain', 'tell me', 'tell me more', 'explain more',
        '1', '2', '3', 'first', 'second', 'pehla', 'doosra',
        // Hindi/Hinglish continuations (extended list)
        'haan', 'han', 'ji', 'ji haan', 'bilkul', 'theek',
        'theek hai', 'thik', 'thik hai',
        'sahi', 'achha', 'acha', 'accha', 'hmm', 'hn',
        'nahi', 'na', 'mat',
        'aur', 'aur batao', 'batao', 'bolo', 'bol',
        'aage', 'agla', 'aage batao',
        'jari', 'chalao', 'phir', 'fir',
        'samjha', 'samjho', 'samjhao', 'samjhao aur',
        // Single Hindi question words (when standalone, they're context-dependent)
        'kya', 'kyun', 'kaise', 'kab', 'kahan',
    ];

    // Greetings — NEVER cache lookup
    private array $greetings = [
        'hi', 'hello', 'hey', 'namaste', 'namaskar',
        'hlo', 'hii', 'hlw', 'good morning', 'good evening',
        'good night', 'gm', 'gn',
    ];

    // Context pronouns without subject — NEVER cache
    private array $contextPhrases = [
        '/^explain\s+(it|this|that)$/i',
        '/^what\s+(is|are)\s+(it|this|that)\??$/i',
        '/^(isko|usko|iske|uske|is|us)\s+(baare|mein)/i',
        '/^(ye|wo|yeh|woh)\s+(kya|kyun|kaise)/i',
    ];

    /**
     * Returns: 'continuation' | 'greeting' | 'context_dependent' | 'short_message' | 'standalone'
     *
     * IMPORTANT: Messages under 20 characters are classified as 'short_message'
     * and should NEVER be cached as standalone queries.
     */
    public function classify(string $message): string
    {
        $msg = trim(mb_strtolower($message));

        // CRITICAL: Short messages (< 20 chars) are NEVER standalone
        // These are almost always continuations or context-dependent
        if (mb_strlen($msg) < self::MIN_CACHEABLE_LENGTH) {
            // Check if it's a greeting first
            if (in_array($msg, $this->greetings)) {
                return 'greeting';
            }
            // Otherwise treat as short_message (never cache)
            return 'short_message';
        }

        // Check exact match continuations
        if (in_array($msg, $this->continuations)) {
            return 'continuation';
        }

        // Check greetings
        if (in_array($msg, $this->greetings)) {
            return 'greeting';
        }

        // Check if starts with continuation word
        // BUT exclude question starters that form complete questions
        $questionStarters = ['what is', 'what are', 'what was', 'what were', 'what does', 'what do',
                             'why is', 'why are', 'why does', 'why do', 'why did',
                             'how is', 'how are', 'how does', 'how do', 'how did', 'how to', 'how can'];

        foreach ($this->continuations as $word) {
            if (str_starts_with($msg, $word . ' ')) {
                // Check if it's actually a question starter (valid standalone question)
                $isValidQuestion = false;
                foreach ($questionStarters as $starter) {
                    if (str_starts_with($msg, $starter)) {
                        $isValidQuestion = true;
                        break;
                    }
                }
                if (!$isValidQuestion) {
                    return 'continuation';
                }
            }
        }

        // Check context-dependent patterns
        foreach ($this->contextPhrases as $pattern) {
            if (preg_match($pattern, $msg)) {
                return 'context_dependent';
            }
        }

        return 'standalone';
    }

    /**
     * Is this message safe for cache lookup?
     */
    public function isCacheSafe(string $message): bool
    {
        return $this->classify($message) === 'standalone';
    }
}
