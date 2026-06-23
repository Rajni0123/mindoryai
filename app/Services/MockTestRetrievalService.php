<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Services\Retrieval\Questions\QuizService;
use App\Services\Retrieval\RetrievalSettingsService;
use Illuminate\Support\Facades\Log;

/**
 * Pulls verified MCQs from Exa / search providers into the exam question pool for mock tests.
 */
class MockTestRetrievalService
{
    public function __construct(
        protected QuizService $quizService,
        protected RetrievalSettingsService $retrievalSettings,
    ) {}

    public function importVerifiedQuestions(
        Exam $exam,
        string $subject,
        string $language,
        int $count,
    ): int {
        if (! $this->hasSearchProvider()) {
            return 0;
        }

        $topic = $this->buildSearchTopic($exam, $language);
        $searchSubject = $subject !== 'all' ? $subject : null;
        $limit = min(max($count, 5), 30);

        try {
            $result = $this->quizService->getVerifiedQuestions($topic, $searchSubject, $limit);
        } catch (\Throwable $e) {
            Log::warning('MockTestRetrieval: search failed', [
                'exam' => $exam->name,
                'topic' => $topic,
                'error' => $e->getMessage(),
            ]);

            return 0;
        }

        if (empty($result['questions'])) {
            Log::info('MockTestRetrieval: no verified questions', [
                'exam' => $exam->name,
                'topic' => $topic,
                'message' => $result['message'] ?? null,
            ]);

            return 0;
        }

        return $this->persistQuestions(
            $exam,
            $result['questions'],
            $subject,
            $language,
            $result['search_providers'] ?? [],
        );
    }

    public function hasSearchProvider(): bool
    {
        if ($this->retrievalSettings->isExaEnabled()) {
            $apiKey = \App\Models\FrontendConfig::getValue('retrieval.exa_api_key')
                ?: config('retrieval.exa.api_key');

            if (! empty($apiKey)) {
                return true;
            }
        }

        return ! empty(config('retrieval.quiz_search.google.api_key'))
            || ! empty(config('retrieval.quiz_search.brave.api_key'))
            || ! empty(config('retrieval.quiz_search.bing.api_key'));
    }

    private function buildSearchTopic(Exam $exam, string $language): string
    {
        $parts = [$exam->name, 'previous year question paper', 'official pdf', 'mcq'];

        if ($language === 'hindi') {
            $parts[] = 'hindi';
        } elseif ($language === 'hinglish') {
            $parts[] = 'hindi english';
        }

        return implode(' ', $parts);
    }

    /**
     * @param  list<array<string, mixed>>  $questions
     * @param  list<string>  $providers
     */
    private function persistQuestions(Exam $exam, array $questions, string $subject, string $language, array $providers): int
    {
        $saved = 0;
        $defaultSubject = $subject !== 'all'
            ? $subject
            : ($this->firstExamSubject($exam) ?? 'General Studies');

        foreach ($questions as $q) {
            $questionText = trim((string) ($q['question'] ?? ''));
            $correct = strtoupper(trim((string) ($q['correct_answer'] ?? '')));

            if ($questionText === '' || $correct === '') {
                continue;
            }

            $options = [];
            $rawOptions = $q['options'] ?? [];
            if (is_array($rawOptions)) {
                foreach ($rawOptions as $label => $text) {
                    $options[] = ['label' => (string) $label, 'text' => (string) $text];
                }
            }

            if (count($options) < 2) {
                continue;
            }

            $exists = ExamQuestion::query()
                ->where('exam_id', $exam->id)
                ->where('question_text', $questionText)
                ->exists();

            if ($exists) {
                continue;
            }

            $year = isset($q['year']) && is_numeric($q['year']) ? (int) $q['year'] : (int) date('Y');
            $provider = (string) ($q['search_provider'] ?? ($providers[0] ?? 'exa'));
            $difficulty = $q['difficulty'] ?? 'medium';
            if (! in_array($difficulty, ['easy', 'medium', 'hard'], true)) {
                $difficulty = 'medium';
            }

            ExamQuestion::create([
                'exam_id' => $exam->id,
                'subject' => $defaultSubject,
                'topic' => (string) ($q['topic'] ?? ''),
                'year' => $year,
                'type' => 'mcq',
                'question_text' => $questionText,
                'options' => $options,
                'correct_answer' => $correct,
                'explanation' => null,
                'difficulty' => $difficulty,
                'language' => $language,
                'tags' => ['real-paper', 'verified-retrieval', $provider, $language],
                'is_active' => true,
            ]);

            $saved++;
        }

        Log::info('MockTestRetrieval: persisted questions', [
            'exam' => $exam->name,
            'saved' => $saved,
            'language' => $language,
            'providers' => $providers,
        ]);

        return $saved;
    }

    private function firstExamSubject(Exam $exam): ?string
    {
        $configured = array_values(array_filter(
            (array) ($exam->subjects ?? []),
            static fn ($value) => is_string($value) && trim($value) !== ''
        ));

        return $configured[0] ?? null;
    }
}
