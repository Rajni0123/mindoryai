<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\MockTest;
use App\Services\ExamQuestionGenerator;
use App\Services\ExamService;
use App\Services\UsageLimitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    protected ExamService $examService;
    protected UsageLimitService $usageLimitService;

    public function __construct(ExamService $examService, UsageLimitService $usageLimitService)
    {
        $this->examService = $examService;
        $this->usageLimitService = $usageLimitService;
    }

    public function index(Request $request): JsonResponse
    {
        $category = $request->query('category');
        $exams = $this->examService->getAvailableExams($category);

        return response()->json([
            'success' => true,
            'data' => $exams,
        ]);
    }

    public function show(int $examId): JsonResponse
    {
        try {
            $detail = $this->examService->getExamDetail($examId);

            return response()->json([
                'success' => true,
                'data' => $detail,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Exam not found.',
            ], 404);
        }
    }

    public function getQuestions(Request $request, int $examId): JsonResponse
    {
        $request->validate([
            'subject' => 'nullable|string',
            'year' => 'nullable|integer',
            'difficulty' => 'nullable|in:easy,medium,hard',
            'per_page' => 'nullable|integer|min:5|max:100',
        ]);

        $query = \App\Models\ExamQuestion::where('exam_id', $examId)->where('is_active', true);

        if ($request->subject) {
            $query->where('subject', $request->subject);
        }
        if ($request->year) {
            $query->where('year', $request->year);
        }
        if ($request->difficulty) {
            $query->where('difficulty', $request->difficulty);
        }

        $questions = $query->orderBy('year', 'desc')
            ->orderBy('subject')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $questions,
        ]);
    }

    public function generateMockTest(Request $request): JsonResponse
    {
        $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'subject' => 'nullable|string',
            'difficulty' => 'nullable|in:easy,medium,hard',
            'year' => 'nullable|integer',
            'question_count' => 'nullable|integer|min:5|max:200',
            'duration_minutes' => 'nullable|integer|min:5|max:300',
        ]);

        $user = $request->user();

        // Check & record exam prep usage
        $check = $this->usageLimitService->checkAndRecord($user, 'exam_prep');
        if (!$check['allowed']) {
            return response()->json([
                'success' => false,
                'message' => $check['message'],
                'upgrade_required' => true,
            ], 429);
        }

        try {
            // Extend execution time for AI question generation
            set_time_limit(300);

            $mockTest = $this->examService->generateMockTest($user, $request->exam_id, [
                'subject' => $request->subject,
                'difficulty' => $request->difficulty,
                'year' => $request->year,
                'question_count' => $request->question_count,
                'duration_minutes' => $request->duration_minutes,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Mock test generated successfully.',
                'data' => $mockTest,
            ]);
        } catch (\Exception $e) {
            \Log::error('ExamController: generateMockTest failed', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'exam_id' => $request->exam_id,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate mock test. Please try again.',
            ], 400);
        }
    }

    public function startMockTest(int $mockTestId): JsonResponse
    {
        $mockTest = MockTest::where('user_id', auth()->id())->findOrFail($mockTestId);

        if ($mockTest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Mock test has already been started or completed.',
            ], 400);
        }

        $mockTest = $this->examService->startMockTest($mockTest);

        // Load the questions for the test
        $questions = \App\Models\ExamQuestion::whereIn('id', $mockTest->question_ids)
            ->get()
            ->map(function ($q) {
                return [
                    'id' => $q->id,
                    'question_text' => $q->question_text,
                    'type' => $q->type,
                    'options' => $q->options,
                    'subject' => $q->subject,
                    'topic' => $q->topic,
                    'difficulty' => $q->difficulty,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'mock_test' => $mockTest,
                'questions' => $questions,
            ],
        ]);
    }

    public function submitMockTest(Request $request, int $mockTestId): JsonResponse
    {
        $request->validate([
            'answers' => 'required|array',
        ]);

        $mockTest = MockTest::where('user_id', auth()->id())->findOrFail($mockTestId);

        if ($mockTest->status === 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Mock test has already been submitted.',
            ], 400);
        }

        $mockTest = $this->examService->submitMockTest($mockTest, $request->answers);

        return response()->json([
            'success' => true,
            'message' => 'Mock test submitted successfully.',
            'data' => [
                'score' => $mockTest->score,
                'correct_answers' => $mockTest->correct_answers,
                'wrong_answers' => $mockTest->wrong_answers,
                'unanswered' => $mockTest->unanswered,
                'accuracy' => $mockTest->accuracy,
                'time_taken' => $mockTest->formatted_time_taken,
            ],
        ]);
    }

    public function getMockTestResult(int $mockTestId): JsonResponse
    {
        $mockTest = MockTest::where('user_id', auth()->id())->findOrFail($mockTestId);

        $result = $this->examService->getMockTestResult($mockTest);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    public function getMockTestHistory(Request $request): JsonResponse
    {
        $history = MockTest::where('user_id', $request->user()->id)
            ->with('exam:id,name,slug,category')
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }

    public function getSubjectAnalysis(Request $request, int $examId): JsonResponse
    {
        $analysis = $this->examService->getSubjectAnalysis($request->user(), $examId);

        return response()->json([
            'success' => true,
            'data' => $analysis,
        ]);
    }

    /**
     * Generate AI-powered PYQ-style questions for an exam.
     */
    public function generateQuestions(Request $request, int $examId): JsonResponse
    {
        $request->validate([
            'subject' => 'nullable|string',
            'year' => 'nullable|integer|min:2000|max:2030',
            'count' => 'nullable|integer|min:5|max:50',
            'difficulty' => 'nullable|in:easy,medium,hard,mixed',
        ]);

        $user = $request->user();

        // Check exam prep usage limit
        $check = $this->usageLimitService->checkAndRecord($user, 'exam_prep');
        if (!$check['allowed']) {
            return response()->json([
                'success' => false,
                'message' => $check['message'],
                'upgrade_required' => true,
            ], 429);
        }

        try {
            // Extend execution time for AI question generation
            set_time_limit(300);

            $exam = Exam::findOrFail($examId);
            $generator = app(ExamQuestionGenerator::class);

            $saved = $generator->generate(
                exam: $exam,
                subject: $request->input('subject', 'all'),
                year: $request->input('year', (int) date('Y')),
                count: $request->input('count', 10),
                difficulty: $request->input('difficulty', 'mixed'),
            );

            return response()->json([
                'success' => true,
                'message' => "Generated {$saved} questions successfully.",
                'data' => [
                    'questions_generated' => $saved,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate questions: ' . $e->getMessage(),
            ], 500);
        }
    }
}
