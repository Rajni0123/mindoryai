<?php

namespace App\Services\Retrieval;

use App\Models\ExamQuestion;
use App\Services\Retrieval\Questions\QuizService;

/**
 * Quiz question sourcing with priority: PYQ → Question Bank → Teacher PDF → Sample → Exa → AI.
 */
class QuizRetrievalEngine
{
    public function __construct(
        protected RetrievalSettingsService $settings,
        protected QuizService $quizService,
    ) {}

    /**
     * @return array{questions: array<int, array<string, mixed>>, source: string, used_ai_fallback: bool}
     */
    public function retrieveQuestions(
        string $topic,
        ?string $subject = null,
        int $limit = 5,
        string $difficulty = 'medium',
    ): array {
        $verified = $this->quizService->getVerifiedQuestions($topic, $subject, $limit);
        if ($verified['questions'] !== []) {
            return [
                'questions' => $verified['questions'],
                'source' => $verified['source'],
                'used_ai_fallback' => false,
            ];
        }

        $fromDb = $this->fromLocalPyq($topic, $subject, $limit, $difficulty);
        if ($fromDb !== []) {
            return [
                'questions' => $fromDb,
                'source' => 'local_pyq',
                'used_ai_fallback' => false,
            ];
        }

        return [
            'questions' => [],
            'source' => 'verified_none',
            'used_ai_fallback' => $this->settings->isAiQuizFallbackEnabled(),
            'message' => 'No verified questions found.',
        ];
    }

    /**
     * @return list<array<string,mixed>>
     */
    protected function fromLocalPyq(string $topic, ?string $subject, int $limit, string $difficulty): array
    {
        $builder = ExamQuestion::query()->active()->where('difficulty', $difficulty);
        if ($subject) {
            $builder->where('subject', 'like', '%' . $subject . '%');
        }

        $builder->where(function ($q) use ($topic) {
            $q->where('topic', 'like', '%' . $topic . '%')
                ->orWhere('subtopic', 'like', '%' . $topic . '%')
                ->orWhere('question_text', 'like', '%' . $topic . '%');
        });

        return $builder->limit($limit)->get()->map(function (ExamQuestion $q) {
            return [
                'question' => $q->question_text,
                'options' => $q->options ?? [],
                'correct_answer' => $q->correct_answer,
                'source_url' => '',
                'pdf_name' => '',
                'exam' => optional($q->exam)->name ?? '',
                'year' => $q->year,
                'topic' => $q->topic ?? '',
                'difficulty' => $q->difficulty ?? 'medium',
            ];
        })->values()->all();
    }
}
