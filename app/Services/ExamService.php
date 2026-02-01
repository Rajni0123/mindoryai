<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\MockTest;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ExamService
{
    protected ExamQuestionGenerator $questionGenerator;

    public function __construct(ExamQuestionGenerator $questionGenerator)
    {
        $this->questionGenerator = $questionGenerator;
    }
    public function getAvailableExams(?string $category = null): Collection
    {
        $query = Exam::active()->orderBy('order');

        if ($category) {
            $query->byCategory($category);
        }

        return $query->get();
    }

    public function getExamDetail(int $examId): array
    {
        $exam = Exam::findOrFail($examId);

        $subjectBreakdown = ExamQuestion::where('exam_id', $examId)
            ->where('is_active', true)
            ->selectRaw('subject, COUNT(*) as question_count')
            ->groupBy('subject')
            ->get();

        $yearBreakdown = ExamQuestion::where('exam_id', $examId)
            ->where('is_active', true)
            ->whereNotNull('year')
            ->selectRaw('year, COUNT(*) as question_count')
            ->groupBy('year')
            ->orderBy('year', 'desc')
            ->get();

        return [
            'exam' => $exam,
            'total_questions' => $exam->getQuestionCount(),
            'subjects' => $subjectBreakdown,
            'years' => $yearBreakdown,
        ];
    }

    public function generateMockTest(User $user, int $examId, array $options = []): MockTest
    {
        $exam = Exam::findOrFail($examId);

        $questionCount = $options['question_count'] ?? $exam->config['total_questions'] ?? 50;
        $subject = $options['subject'] ?? 'all';
        $year = $options['year'] ?? (int) date('Y');
        $difficulty = $options['difficulty'] ?? 'mixed';
        $isBoardExam = in_array($exam->category, ['board']);

        // Check how many questions we have
        $existingQuery = ExamQuestion::where('exam_id', $examId)->where('is_active', true);
        if ($subject !== 'all') {
            $existingQuery->where('subject', $subject);
        }
        if (!empty($options['year'])) {
            $existingQuery->where('year', $year);
        }
        if ($difficulty !== 'mixed') {
            $existingQuery->where('difficulty', $difficulty);
        }

        $existingCount = $existingQuery->count();

        // For board exams (CBSE), prefer real PYQ questions - do NOT auto-generate
        // For competitive exams (JEE, NEET etc.), auto-generate if needed
        if ($existingCount < $questionCount && !$isBoardExam) {
            $needed = $questionCount - $existingCount;
            Log::info("ExamService: auto-generating {$needed} questions for {$exam->name}", [
                'subject' => $subject,
                'year' => $year,
                'existing' => $existingCount,
                'needed' => $needed,
            ]);

            try {
                $this->questionGenerator->generate($exam, $subject, $year, $needed, $difficulty);
            } catch (\Exception $e) {
                Log::error("ExamService: auto-generation failed", ['error' => $e->getMessage()]);
            }
        }

        // For board exams: fetch all available real PYQ questions (across years if needed)
        $query = ExamQuestion::where('exam_id', $examId)->where('is_active', true);

        if ($subject !== 'all') {
            $query->where('subject', $subject);
        }

        if ($difficulty !== 'mixed') {
            $query->where('difficulty', $difficulty);
        }

        if (!empty($options['year'])) {
            $query->where('year', $year);
        }

        // For board exams, prioritize real PYQ (tagged 'real-paper') and order by year desc
        if ($isBoardExam) {
            $query->where(function ($q) {
                $q->where('tags', 'like', '%real-paper%');
            })->orderBy('year', 'desc');

            // If no real PYQ found, fallback to all available questions
            if ($query->count() === 0) {
                $query = ExamQuestion::where('exam_id', $examId)->where('is_active', true);
                if ($subject !== 'all') $query->where('subject', $subject);
                if ($difficulty !== 'mixed') $query->where('difficulty', $difficulty);
                if (!empty($options['year'])) $query->where('year', $year);
            }
        }

        $questions = $query->inRandomOrder()->limit($questionCount)->get();

        $duration = $options['duration_minutes'] ?? $exam->config['duration_minutes'] ?? 60;

        $title = $exam->name . ' Mock Test';
        if (!empty($options['subject'])) {
            $title .= ' - ' . $options['subject'];
        }
        if (!empty($options['year'])) {
            $title .= ' (' . $options['year'] . ')';
        }

        // Check if questions are real PYQ
        $hasRealPYQ = $questions->contains(function ($q) {
            return is_array($q->tags) && in_array('real-paper', $q->tags);
        });

        return MockTest::create([
            'user_id' => $user->id,
            'exam_id' => $examId,
            'title' => $title,
            'question_ids' => $questions->pluck('id')->toArray(),
            'total_questions' => $questions->count(),
            'duration_minutes' => $duration,
            'config' => [
                'marking_scheme' => $exam->config['marking_scheme'] ?? ['correct' => 4, 'wrong' => -1],
                'negative_marking' => $exam->config['negative_marking'] ?? true,
                'is_real_pyq' => $hasRealPYQ,
                'source' => $hasRealPYQ ? 'cbse-board-paper' : 'ai-generated',
            ],
            'status' => 'pending',
        ]);
    }

    public function startMockTest(MockTest $mockTest): MockTest
    {
        $mockTest->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        return $mockTest;
    }

    public function submitMockTest(MockTest $mockTest, array $answers): MockTest
    {
        $questionIds = $mockTest->question_ids;
        $questions = ExamQuestion::whereIn('id', $questionIds)->get()->keyBy('id');

        $correct = 0;
        $wrong = 0;
        $unanswered = 0;

        $markingScheme = $mockTest->config['marking_scheme'] ?? ['correct' => 4, 'wrong' => -1];

        foreach ($questionIds as $qId) {
            $question = $questions->get($qId);
            if (!$question) continue;

            $userAnswer = $answers[$qId] ?? null;

            if ($userAnswer === null || $userAnswer === '') {
                $unanswered++;
            } elseif ($question->isCorrect($userAnswer)) {
                $correct++;
            } else {
                $wrong++;
            }
        }

        $score = ($correct * ($markingScheme['correct'] ?? 4)) + ($wrong * ($markingScheme['wrong'] ?? -1));
        $score = max(0, $score);

        $mockTest->update([
            'status' => 'completed',
            'answers' => $answers,
            'correct_answers' => $correct,
            'wrong_answers' => $wrong,
            'unanswered' => $unanswered,
            'score' => $score,
            'time_taken_seconds' => $mockTest->started_at ? max(0, (int) abs(now()->diffInSeconds($mockTest->started_at))) : null,
            'completed_at' => now(),
        ]);

        return $mockTest->fresh();
    }

    public function getMockTestResult(MockTest $mockTest): array
    {
        $questions = ExamQuestion::whereIn('id', $mockTest->question_ids)->get()->keyBy('id');
        $answers = $mockTest->answers ?? [];

        $questionResults = [];
        foreach ($mockTest->question_ids as $qId) {
            $question = $questions->get($qId);
            if (!$question) continue;

            $userAnswer = $answers[$qId] ?? null;
            $questionResults[] = [
                'question' => $question,
                'user_answer' => $userAnswer,
                'is_correct' => $userAnswer ? $question->isCorrect($userAnswer) : false,
                'is_unanswered' => $userAnswer === null || $userAnswer === '',
            ];
        }

        return [
            'mock_test' => $mockTest,
            'questions' => $questionResults,
            'summary' => [
                'total' => $mockTest->total_questions,
                'correct' => $mockTest->correct_answers,
                'wrong' => $mockTest->wrong_answers,
                'unanswered' => $mockTest->unanswered,
                'score' => $mockTest->score,
                'accuracy' => $mockTest->accuracy,
                'time_taken' => $mockTest->formatted_time_taken,
            ],
        ];
    }

    public function getSubjectAnalysis(User $user, int $examId): array
    {
        $completedTests = MockTest::where('user_id', $user->id)
            ->where('exam_id', $examId)
            ->completed()
            ->get();

        if ($completedTests->isEmpty()) {
            return ['message' => 'No completed tests found', 'data' => []];
        }

        $allQuestionIds = $completedTests->pluck('question_ids')->flatten()->unique();
        $allAnswers = [];
        foreach ($completedTests as $test) {
            if ($test->answers) {
                $allAnswers = array_merge($allAnswers, $test->answers);
            }
        }

        $questions = ExamQuestion::whereIn('id', $allQuestionIds)->get();
        $subjectStats = [];

        foreach ($questions as $question) {
            $subject = $question->subject;
            if (!isset($subjectStats[$subject])) {
                $subjectStats[$subject] = ['total' => 0, 'correct' => 0, 'wrong' => 0, 'unanswered' => 0];
            }

            $subjectStats[$subject]['total']++;
            $answer = $allAnswers[$question->id] ?? null;
            if ($answer === null || $answer === '') {
                $subjectStats[$subject]['unanswered']++;
            } elseif ($question->isCorrect($answer)) {
                $subjectStats[$subject]['correct']++;
            } else {
                $subjectStats[$subject]['wrong']++;
            }
        }

        foreach ($subjectStats as $subject => &$stats) {
            $stats['accuracy'] = $stats['total'] > 0
                ? round(($stats['correct'] / $stats['total']) * 100, 1)
                : 0;
        }

        return [
            'total_tests' => $completedTests->count(),
            'average_score' => round($completedTests->avg('score'), 1),
            'best_score' => $completedTests->max('score'),
            'subjects' => $subjectStats,
        ];
    }

    public function getPYQByYear(int $examId, int $year, ?string $subject = null): Collection
    {
        $query = ExamQuestion::where('exam_id', $examId)
            ->where('year', $year)
            ->where('is_active', true);

        if ($subject) {
            $query->where('subject', $subject);
        }

        return $query->orderBy('subject')
            ->orderBy('difficulty')
            ->get();
    }

    /**
     * Get available PYQ years for an exam (years that actually have questions in DB).
     */
    public function getAvailablePYQYears(int $examId): array
    {
        $years = ExamQuestion::where('exam_id', $examId)
            ->where('is_active', true)
            ->whereNotNull('year')
            ->selectRaw('year, COUNT(*) as question_count')
            ->groupBy('year')
            ->orderBy('year', 'desc')
            ->get();

        return $years->map(function ($row) {
            return [
                'year' => $row->year,
                'question_count' => $row->question_count,
            ];
        })->toArray();
    }

    /**
     * Get a full PYQ paper for a given year and optional subject.
     * Returns questions in original paper order (by section/difficulty).
     */
    public function getPYQPaper(int $examId, int $year, ?string $subject = null): array
    {
        $exam = Exam::findOrFail($examId);

        $query = ExamQuestion::where('exam_id', $examId)
            ->where('year', $year)
            ->where('is_active', true);

        if ($subject) {
            $query->where('subject', $subject);
        }

        // Order by difficulty (easy=Section A, medium=Section B/C, hard=Section D)
        $questions = $query->orderByRaw("FIELD(difficulty, 'easy', 'medium', 'hard')")
            ->orderBy('subject')
            ->get();

        // Get available subjects for this year
        $availableSubjects = ExamQuestion::where('exam_id', $examId)
            ->where('year', $year)
            ->where('is_active', true)
            ->selectRaw('subject, COUNT(*) as question_count')
            ->groupBy('subject')
            ->get();

        return [
            'exam' => [
                'id' => $exam->id,
                'name' => $exam->name,
                'slug' => $exam->slug,
                'category' => $exam->category,
                'config' => $exam->config,
            ],
            'year' => $year,
            'subject' => $subject,
            'total_questions' => $questions->count(),
            'total_marks' => $exam->config['total_marks'] ?? 80,
            'duration_minutes' => $exam->config['duration_minutes'] ?? 180,
            'sections' => $exam->config['sections'] ?? null,
            'available_subjects' => $availableSubjects,
            'questions' => $questions->map(function ($q) {
                return [
                    'id' => $q->id,
                    'question_text' => $q->question_text,
                    'type' => $q->type,
                    'options' => $q->options,
                    'subject' => $q->subject,
                    'topic' => $q->topic,
                    'difficulty' => $q->difficulty,
                    'tags' => $q->tags,
                ];
            }),
        ];
    }

    /**
     * Create a mock test from a full PYQ paper (all questions from that year, in order).
     */
    public function createPYQMockTest(User $user, int $examId, int $year, ?string $subject = null): MockTest
    {
        $exam = Exam::findOrFail($examId);

        $query = ExamQuestion::where('exam_id', $examId)
            ->where('year', $year)
            ->where('is_active', true);

        if ($subject) {
            $query->where('subject', $subject);
        }

        // Get ALL questions for this year (not random - preserve original paper order)
        $questions = $query->orderByRaw("FIELD(difficulty, 'easy', 'medium', 'hard')")
            ->orderBy('subject')
            ->get();

        if ($questions->isEmpty()) {
            throw new \Exception("No PYQ questions found for {$exam->name} {$year}" . ($subject ? " - {$subject}" : ""));
        }

        $title = "{$exam->name} {$year} Previous Year Paper";
        if ($subject) {
            $title = "{$exam->name} {$subject} {$year} - Previous Year Paper";
        }

        return MockTest::create([
            'user_id' => $user->id,
            'exam_id' => $examId,
            'title' => $title,
            'question_ids' => $questions->pluck('id')->toArray(),
            'total_questions' => $questions->count(),
            'duration_minutes' => $exam->config['duration_minutes'] ?? 180,
            'config' => [
                'marking_scheme' => $exam->config['marking_scheme'] ?? ['correct' => 1, 'wrong' => 0],
                'negative_marking' => $exam->config['negative_marking'] ?? false,
                'is_pyq' => true,
                'pyq_year' => $year,
                'pyq_subject' => $subject,
            ],
            'status' => 'pending',
        ]);
    }
}
