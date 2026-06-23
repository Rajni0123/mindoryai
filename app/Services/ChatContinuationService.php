<?php

namespace App\Services;

/**
 * Expands short follow-up chat messages using recent conversation history.
 */
class ChatContinuationService
{
    /**
     * @param  list<array{role: string, content: string}>  $history
     */
    public function expandMessage(string $message, array $history): string
    {
        $clean = trim($message);

        if ($clean === '' || $history === []) {
            return $message;
        }

        if (! $this->shouldExpand($clean, $history)) {
            return $message;
        }

        $lastAssistant = $this->lastMessageByRole($history, 'assistant');
        $lastUserTopic = $this->lastSubstantiveUserMessage($history);

        if ($lastAssistant === null && $lastUserTopic === null) {
            return $message;
        }

        $assistantSnippet = $lastAssistant
            ? substr(strtok($lastAssistant, "\n") ?: $lastAssistant, 0, 220)
            : '';
        $topic = $lastUserTopic ?: $assistantSnippet;

        return "[FOLLOW-UP REPLY]\n"
            . "ORIGINAL STUDENT TOPIC: \"{$topic}\"\n"
            . ($assistantSnippet !== '' ? "YOUR LAST MESSAGE: \"{$assistantSnippet}\"\n" : '')
            . "STUDENT FOLLOW-UP: \"{$clean}\"\n"
            . "IMPORTANT:\n"
            . "- This is a continuation of the same conversation, NOT a new greeting.\n"
            . "- Do NOT repeat \"Main Blinky hoon\" or ask \"Aaj kya padhna hai?\" again.\n"
            . "- Answer the follow-up directly using chat history.\n"
            . "- If the student asked for exam papers/resources, provide Prelims + Mains PYQ guidance, official links, or steps to download when possible.\n"
            . "- If web search context is available, use it and cite sources.";
    }

    /**
     * @param  list<array{role: string, content: string}>  $history
     */
    private function shouldExpand(string $message, array $history): bool
    {
        if ($this->isPureGreeting($message)) {
            return false;
        }

        if ($this->looksLikeStandaloneQuestion($message)) {
            return false;
        }

        $lower = mb_strtolower($message);

        if (mb_strlen($message) <= 40) {
            return true;
        }

        $followUpPhrases = [
            'dono', 'dono chahiye', 'both', 'haan', 'han', 'yes', 'yeah', 'ok', 'okay',
            'prelims', 'mains', 'prelim', 'main', 'pehla', 'dusra', 'sab', 'sabhi',
            'aur batao', 'aur do', 'continue', 'detail', 'details', 'link', 'links',
            'pdf', 'download', 'paper do', 'papers do',
        ];

        foreach ($followUpPhrases as $phrase) {
            if ($lower === $phrase || str_starts_with($lower, $phrase . ' ')) {
                return true;
            }
        }

        return false;
    }

    private function isPureGreeting(string $message): bool
    {
        $lower = mb_strtolower(trim($message));

        return (bool) preg_match('/^(hi+|hii+|hey+|hello+|namaste|namaskar|good\s+(morning|afternoon|evening))[\s\.\!\?]*$/u', $lower);
    }

    private function looksLikeStandaloneQuestion(string $message): bool
    {
        if (mb_strlen($message) >= 60) {
            return true;
        }

        $lower = mb_strtolower($message);
        $keywords = [
            'what', 'why', 'how', 'explain', 'define', 'solve', 'calculate',
            'kya', 'kaise', 'kyun', 'samjhao', 'batao', 'paper', 'pyq', 'upsc',
            'jee', 'neet', 'exam', 'question', 'quiz', 'topic',
        ];

        foreach ($keywords as $keyword) {
            if (str_contains($lower, $keyword)) {
                return mb_strlen($message) > 25;
            }
        }

        return false;
    }

    /**
     * @param  list<array{role: string, content: string}>  $history
     */
    private function lastMessageByRole(array $history, string $role): ?string
    {
        for ($i = count($history) - 1; $i >= 0; $i--) {
            if (($history[$i]['role'] ?? '') === $role) {
                $content = trim((string) ($history[$i]['content'] ?? ''));

                return $content !== '' ? $content : null;
            }
        }

        return null;
    }

    /**
     * @param  list<array{role: string, content: string}>  $history
     */
    private function lastSubstantiveUserMessage(array $history): ?string
    {
        $skipPatterns = '/^(yes|ok|ha+n?|sure|continue|thanks?|dono|both|prelims?|mains?)[\s\.\!\?]*$/iu';

        for ($i = count($history) - 1; $i >= 0; $i--) {
            if (($history[$i]['role'] ?? '') !== 'user') {
                continue;
            }

            $content = trim((string) ($history[$i]['content'] ?? ''));
            if ($content === '') {
                continue;
            }

            $display = $content;
            if (preg_match('/^\[(Search|Think|Canvas):\s*(.+)\]$/su', $content, $matches)) {
                $display = trim($matches[2]);
            }

            $lower = mb_strtolower($display);
            if ($this->isPureGreeting($display)) {
                continue;
            }

            if (preg_match($skipPatterns, $lower)) {
                continue;
            }

            if (mb_strlen($display) >= 3) {
                return $display;
            }
        }

        return null;
    }
}
