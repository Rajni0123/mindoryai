<?php

namespace App\Services\Retrieval\Questions;

class QuestionRanker
{
    /**
     * @param  list<array<string, mixed>>  $questions
     * @return list<array<string, mixed>>
     */
    public function rank(array $questions, string $topic, ?string $subject): array
    {
        $topicNeedle = mb_strtolower($topic);
        $subjectNeedle = mb_strtolower((string) $subject);

        usort($questions, function (array $a, array $b) use ($topicNeedle, $subjectNeedle) {
            $sa = $this->score($a, $topicNeedle, $subjectNeedle);
            $sb = $this->score($b, $topicNeedle, $subjectNeedle);

            return $sb <=> $sa;
        });

        return $questions;
    }

    /**
     * @param  array<string, mixed>  $question
     */
    private function score(array $question, string $topicNeedle, string $subjectNeedle): int
    {
        $score = 0;
        $text = mb_strtolower((string) ($question['question'] ?? ''));
        $topic = mb_strtolower((string) ($question['topic'] ?? ''));
        $exam = mb_strtolower((string) ($question['exam'] ?? ''));

        if ($topicNeedle !== '' && str_contains($text, $topicNeedle)) {
            $score += 5;
        }
        if ($topicNeedle !== '' && str_contains($topic, $topicNeedle)) {
            $score += 4;
        }
        if ($subjectNeedle !== '' && str_contains($exam, $subjectNeedle)) {
            $score += 2;
        }
        if ((string) ($question['correct_answer'] ?? '') !== '') {
            $score += 2;
        }
        if (! empty($question['source_url'])) {
            $score += 1;
        }

        return $score;
    }
}

