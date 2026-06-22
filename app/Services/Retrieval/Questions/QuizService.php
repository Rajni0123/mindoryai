<?php

namespace App\Services\Retrieval\Questions;

class QuizService
{
    public function __construct(
        protected RetrievalEngine $retrievalEngine,
        protected QuestionExtractionEngine $extractionEngine,
        protected QuestionRepository $questionRepository,
    ) {}

    /**
     * @return array{questions:list<array<string,mixed>>,source:string,verified:bool,message:?string}
     */
    public function getVerifiedQuestions(string $topic, ?string $subject, int $limit): array
    {
        $cacheKey = implode('|', ['verified-quiz', mb_strtolower($topic), mb_strtolower((string) $subject), $limit]);

        $questions = $this->questionRepository->getOrStore($cacheKey, function () use ($topic, $subject, $limit) {
            $docs = $this->retrievalEngine->retrieveQuizDocuments($topic, $subject, max(8, $limit * 2));
            if ($docs === []) {
                return [];
            }

            return $this->extractionEngine->extractQuestions($docs, $topic, $subject, $limit);
        });

        if ($questions === []) {
            return [
                'questions' => [],
                'source' => 'exa_real_retrieval',
                'verified' => false,
                'message' => 'No verified questions found.',
            ];
        }

        return [
            'questions' => $questions,
            'source' => 'exa_real_retrieval',
            'verified' => true,
            'message' => null,
        ];
    }
}

