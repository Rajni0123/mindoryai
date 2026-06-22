<?php

namespace App\Services\Retrieval\Questions;

class QuestionParser
{
    /**
     * @return array<string, mixed>|null
     */
    public function parse(string $block): ?array
    {
        $text = trim($block);
        if ($text === '') {
            return null;
        }

        $lines = array_values(array_filter(array_map('trim', preg_split('/\n+/', $text) ?: [])));
        if ($lines === []) {
            return null;
        }

        $questionLine = null;
        foreach ($lines as $line) {
            if (preg_match('/\?$/u', $line) || preg_match('/^\d+[\.\)]\s+/u', $line)) {
                $questionLine = preg_replace('/^\d+[\.\)]\s*/u', '', $line);
                break;
            }
        }
        if (! $questionLine) {
            $questionLine = $lines[0] ?? '';
        }

        $options = [];
        foreach ($lines as $line) {
            if (preg_match('/^\(?([A-D])\)?[\.\:\)]\s*(.+)$/iu', $line, $m)) {
                $options[strtoupper($m[1])] = trim($m[2]);
            }
        }

        if (count($options) < 2) {
            return null;
        }

        $correct = '';
        foreach ($lines as $line) {
            if (preg_match('/(Answer|Ans)\s*[:\-]?\s*\(?([A-D])\)?/iu', $line, $m)) {
                $correct = strtoupper($m[2]);
                break;
            }
        }

        return [
            'question' => trim((string) $questionLine),
            'options' => $options,
            'correct_answer' => $correct,
        ];
    }
}

