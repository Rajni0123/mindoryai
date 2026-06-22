<?php

namespace App\Services\Retrieval\Questions;

class MCQDetector
{
    /**
     * @return list<string>
     */
    public function detectBlocks(string $text): array
    {
        if ($text === '') {
            return [];
        }

        $normalized = preg_replace('/[ \t]+/', ' ', $text) ?? $text;
        $chunks = preg_split('/\n{2,}/', $normalized) ?: [];
        $blocks = [];
        $buffer = '';

        foreach ($chunks as $chunk) {
            $buffer .= "\n" . trim($chunk);
            $hasQuestion = preg_match('/\b\d+\s*[\.\)]\s+.+\?/u', $buffer) === 1 || preg_match('/\b(Q\.?|Question)\s*\d+/iu', $buffer) === 1;
            $hasOptions = preg_match('/\bA[\)\.\:]|\(A\)|\nA\s+/u', $buffer) === 1
                && preg_match('/\bB[\)\.\:]|\(B\)|\nB\s+/u', $buffer) === 1;

            if ($hasQuestion && $hasOptions) {
                $blocks[] = trim($buffer);
                $buffer = '';
            }
        }

        return $blocks;
    }
}

