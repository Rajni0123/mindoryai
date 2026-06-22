<?php

namespace App\Services\Retrieval\Questions;

class QuestionNormalizer
{
    /**
     * @param  array<string, mixed>  $question
     * @return array<string, mixed>
     */
    public function normalize(array $question): array
    {
        $questionText = trim((string) ($question['question'] ?? ''));
        $options = $question['options'] ?? [];
        $normalizedOptions = [];

        if (is_array($options)) {
            foreach ($options as $k => $v) {
                $key = strtoupper(trim((string) $k));
                if ($key === '') {
                    continue;
                }
                $normalizedOptions[$key] = trim((string) $v);
            }
            ksort($normalizedOptions);
        }

        return [
            ...$question,
            'question' => preg_replace('/\s+/', ' ', $questionText) ?: $questionText,
            'options' => $normalizedOptions,
            'correct_answer' => strtoupper(trim((string) ($question['correct_answer'] ?? ''))),
            'difficulty' => $question['difficulty'] ?? 'medium',
            'topic' => $question['topic'] ?? '',
            'exam' => $question['exam'] ?? '',
            'year' => $question['year'] ?? null,
        ];
    }
}

