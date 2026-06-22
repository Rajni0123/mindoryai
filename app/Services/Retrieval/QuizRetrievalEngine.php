<?php

namespace App\Services\Retrieval;

use App\Models\ExamQuestion;
use App\Models\KnowledgeSource;
use App\Models\QuestionBank;
use App\Services\Retrieval\DTO\RetrievalQuery;

/**
 * Quiz question sourcing with priority: PYQ → Question Bank → Teacher PDF → Sample → Exa → AI.
 */
class QuizRetrievalEngine
{
    public function __construct(
        protected RetrievalSettingsService $settings,
        protected ExaSearchService $exaSearchService,
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
        $priorities = $this->settings->quizPriority();
        $query = new RetrievalQuery(
            question: $topic,
            subject: $subject,
            topic: $topic,
            feature: 'quiz',
        );

        foreach ($priorities as $source) {
            $questions = match ($source) {
                'pyq' => $this->fromPyq($topic, $subject, $limit, $difficulty),
                'question_bank' => $this->fromQuestionBank($topic, $subject, $limit),
                'teacher_pdf', 'sample_paper' => $this->fromKnowledgeSource($source, $topic, $subject, $limit),
                'exa' => $this->fromExa($query, $limit),
                'ai_generation' => [],
                default => [],
            };

            if ($questions !== []) {
                return [
                    'questions' => array_slice($questions, 0, $limit),
                    'source' => $source,
                    'used_ai_fallback' => false,
                ];
            }
        }

        return [
            'questions' => [],
            'source' => 'ai_generation',
            'used_ai_fallback' => $this->settings->isAiQuizFallbackEnabled(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function fromPyq(string $topic, ?string $subject, int $limit, string $difficulty): array
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
                'explanation' => $q->explanation,
                'difficulty' => $q->difficulty,
                'source' => 'pyq',
            ];
        })->values()->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function fromQuestionBank(string $topic, ?string $subject, int $limit): array
    {
        $builder = QuestionBank::query();

        if ($subject) {
            $builder->where('subject', 'like', '%' . $subject . '%');
        }

        $builder->where(function ($q) use ($topic) {
            $q->where('topic', 'like', '%' . $topic . '%')
                ->orWhere('normalized_question', 'like', '%' . $topic . '%');
        });

        return $builder->limit($limit)->get()->map(function (QuestionBank $q) {
            $answer = json_decode($q->answer, true);

            return is_array($answer) ? array_merge($answer, ['source' => 'question_bank']) : [
                'question' => $q->original_question,
                'explanation' => $q->answer,
                'source' => 'question_bank',
            ];
        })->values()->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function fromKnowledgeSource(string $type, string $topic, ?string $subject, int $limit): array
    {
        $sources = KnowledgeSource::query()
            ->where('is_active', true)
            ->where('type', $type === 'teacher_pdf' ? 'pdf' : 'sample_paper')
            ->limit(3)
            ->get();

        if ($sources->isEmpty()) {
            return [];
        }

        // Real question extraction from chunks is domain-specific; return empty to allow next priority.
        return [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function fromExa(RetrievalQuery $query, int $limit): array
    {
        if (! $this->settings->isExaEnabled()) {
            return [];
        }

        $result = $this->exaSearchService->search($query, $limit);
        if (! $result->success) {
            return [];
        }

        // Exa returns context, not structured MCQs — defer to AI explanation layer.
        return [];
    }
}
