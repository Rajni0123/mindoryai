<?php

namespace App\Services\Cache;

class MessageClassifier
{
    // Continuation words — NEVER cache lookup
    private array $continuations = [
        'yes', 'yeah', 'yep', 'yup', 'ok', 'okay', 'sure',
        'haan', 'han', 'ji', 'ji haan', 'bilkul', 'theek',
        'sahi', 'achha', 'acha', 'hmm', 'hn',
        'more', 'aage', 'aur', 'aur batao', 'batao',
        'continue', 'jari', 'chalao', 'phir',
        'next', 'agla', 'further',
        'tell me more', 'explain more', 'samjhao aur',
        '1', '2', '3', 'first', 'second', 'pehla', 'doosra',
    ];

    // Greetings — NEVER cache lookup
    private array $greetings = [
        'hi', 'hello', 'hey', 'namaste', 'namaskar',
        'hlo', 'hii', 'hlw', 'good morning', 'good evening',
    ];

    // Context pronouns without subject — NEVER cache
    private array $contextPhrases = [
        '/^explain\s+(it|this|that)$/i',
        '/^what\s+(is|are)\s+(it|this|that)\??$/i',
        '/^(isko|usko|iske|uske|is|us)\s+(baare|mein)/i',
        '/^(ye|wo|yeh|woh)\s+(kya|kyun|kaise)/i',
    ];

    /**
     * Returns: 'continuation' | 'greeting' | 'context_dependent' | 'standalone'
     */
    public function classify(string $message): string
    {
        $msg = trim(mb_strtolower($message));

        // Check exact match continuations
        if (in_array($msg, $this->continuations)) {
            return 'continuation';
        }

        // Check greetings
        if (in_array($msg, $this->greetings)) {
            return 'greeting';
        }

        // Check if starts with continuation word
        foreach ($this->continuations as $word) {
            if (str_starts_with($msg, $word . ' ')) {
                return 'continuation';
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
