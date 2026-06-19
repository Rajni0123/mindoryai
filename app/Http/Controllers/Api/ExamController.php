<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\MockTest;
use App\Services\ExamQuestionGenerator;
use App\Services\ExamService;
use App\Services\UsageLimitService;
use App\Support\ApiValidator;
use App\Support\ResourceAuthorizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

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
        $validated = ApiValidator::validateQuery($request, [
            'category' => ApiValidator::safeString(50, false),
        ]);
        $category = $validated['category'] ?? null;

        // Cache exam list for 5 minutes for faster response
        $cacheKey = 'exams_list_' . ($category ?? 'all');
        $exams = Cache::remember($cacheKey, 300, function () use ($category) {
            return $this->examService->getAvailableExams($category);
        });

        return response()->json([
            'success' => true,
            'data' => $exams,
        ]);
    }

    public function show(int $examId): JsonResponse
    {
        try {
            // Cache exam details for 5 minutes
            $cacheKey = "exam_detail_{$examId}";
            $detail = Cache::remember($cacheKey, 300, function () use ($examId) {
                $detail = $this->examService->getExamDetail($examId);

                // Get actual available question count from database
                $exam = \App\Models\Exam::find($examId);
                $actualQuestionCount = \App\Models\ExamQuestion::where('exam_id', $examId)
                    ->where('is_active', true)
                    ->count();

                // Add dynamic values based on actual available questions
                $configQuestions = $exam->config['total_questions'] ?? 50;
                $configDuration = $exam->config['duration_minutes'] ?? 60;

                // Calculate dynamic duration based on available questions
                // Assume ~2-3 minutes per question for MCQ tests
                $minutesPerQuestion = $exam->category === 'board' ? 3 : 2;
                $dynamicDuration = min($configDuration, $actualQuestionCount * $minutesPerQuestion);
                $dynamicDuration = max(15, $dynamicDuration); // Minimum 15 minutes

                $detail['dynamic_info'] = [
                    'available_questions' => $actualQuestionCount,
                    'suggested_duration' => $dynamicDuration,
                    'config_questions' => $configQuestions,
                    'config_duration' => $configDuration,
                ];

                // Add PYQ availability info for board exams
                if ($exam && $exam->category === 'board') {
                    $realPYQCount = \App\Models\ExamQuestion::where('exam_id', $examId)
                        ->where('is_active', true)
                        ->where('tags', 'like', '%real-paper%')
                        ->count();

                    $detail['pyq_info'] = [
                        'has_real_pyq' => $realPYQCount > 0,
                        'real_question_count' => $realPYQCount,
                        'pyq_available' => $exam->config['pyq_available'] ?? false,
                        'pyq_years' => $exam->config['pyq_years'] ?? [],
                        'source' => $realPYQCount > 0 ? 'Real CBSE Board Papers' : 'AI Generated',
                    ];
                }

                return $detail;
            });

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

        $questions->getCollection()->transform(function ($question) {
            return [
                'id' => $question->id,
                'exam_id' => $question->exam_id,
                'subject' => $question->subject,
                'topic' => $question->topic,
                'subtopic' => $question->subtopic,
                'year' => $question->year,
                'type' => $question->type,
                'question_text' => $question->question_text,
                'options' => $question->options,
                'difficulty' => $question->difficulty,
                'language' => $question->language,
                'tags' => $question->tags,
            ];
        });

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
                'message' => $check['reason'],
                'upgrade_required' => true,
            ], 429);
        }

        try {
            // Extend execution time for AI question generation (reduced from 300)
            set_time_limit(120);

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
        $mockTest = ResourceAuthorizer::ownedMockTest(auth()->user(), $mockTestId);

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
                $isRealPYQ = is_array($q->tags) && in_array('real-paper', $q->tags);

                // Normalize options format to [{label: "A", text: "..."}, ...]
                $options = $q->options ?? [];
                $normalizedOptions = [];

                // Check if options is in old format {A: "...", B: "..."} vs new [{label, text}]
                if (is_array($options) && !empty($options)) {
                    // Check first element to determine format
                    $first = reset($options);
                    $firstKey = key($options);

                    if (is_string($first) && is_string($firstKey) && strlen($firstKey) === 1) {
                        // Old format: {A: "text", B: "text", ...}
                        foreach ($options as $label => $text) {
                            $normalizedOptions[] = [
                                'label' => (string) $label,
                                'text' => (string) $text,
                            ];
                        }
                    } elseif (isset($first['label']) && isset($first['text'])) {
                        // Already in new format - but ensure strings
                        foreach ($options as $opt) {
                            $normalizedOptions[] = [
                                'label' => (string) ($opt['label'] ?? ''),
                                'text' => (string) ($opt['text'] ?? ''),
                            ];
                        }
                    } elseif (is_string($first)) {
                        // Plain array of strings format: ["Option 1", "Option 2", ...]
                        $labels = ['A', 'B', 'C', 'D', 'E', 'F'];
                        foreach (array_values($options) as $i => $text) {
                            $normalizedOptions[] = [
                                'label' => $labels[$i] ?? chr(65 + $i),
                                'text' => (string) $text,
                            ];
                        }
                    } else {
                        // Unknown format, try to extract
                        foreach ($options as $key => $value) {
                            if (is_array($value)) {
                                $normalizedOptions[] = [
                                    'label' => (string) ($value['label'] ?? $key),
                                    'text' => (string) ($value['text'] ?? $value['option'] ?? ''),
                                ];
                            } else {
                                $normalizedOptions[] = [
                                    'label' => is_numeric($key) ? chr(65 + $key) : (string) $key,
                                    'text' => (string) $value,
                                ];
                            }
                        }
                    }
                }

                return [
                    'id' => (int) $q->id,
                    'question_text' => (string) ($q->question_text ?? ''),
                    'type' => (string) ($q->type ?? 'mcq'),
                    'options' => $normalizedOptions,
                    'subject' => (string) ($q->subject ?? ''),
                    'topic' => (string) ($q->topic ?? ''),
                    'difficulty' => (string) ($q->difficulty ?? 'medium'),
                    'year' => $q->year ? (int) $q->year : null,
                    'is_real_pyq' => (bool) $isRealPYQ,
                    'source' => (string) ($isRealPYQ ? 'CBSE Board Paper' : 'AI Generated'),
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

        $mockTest = ResourceAuthorizer::ownedMockTest($request->user(), $mockTestId);

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
        $mockTest = ResourceAuthorizer::ownedMockTest(auth()->user(), $mockTestId);

        $result = $this->examService->getMockTestResult($mockTest);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    public function getMockTestHistory(Request $request): JsonResponse
    {
        $validated = ApiValidator::validateQuery($request, [
            'per_page' => ApiValidator::paginationLimit(15, 50),
        ]);

        $history = MockTest::where('user_id', $request->user()->id)
            ->with('exam:id,name,slug,category')
            ->orderBy('created_at', 'desc')
            ->paginate($validated['per_page'] ?? 15);

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
     * Get available PYQ years for an exam.
     * GET /api/exams/{exam}/pyq-years
     */
    public function getAvailablePYQYears(int $examId): JsonResponse
    {
        try {
            // Cache PYQ years for 10 minutes
            $cacheKey = "pyq_years_{$examId}";
            $years = Cache::remember($cacheKey, 600, function () use ($examId) {
                return $this->examService->getAvailablePYQYears($examId);
            });

            return response()->json([
                'success' => true,
                'data' => $years,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get PYQ years.',
            ], 400);
        }
    }

    /**
     * Get a full PYQ paper for a specific year.
     * GET /api/exams/{exam}/pyq/{year}?subject=Mathematics
     */
    public function getPYQPaper(Request $request, int $examId, int $year): JsonResponse
    {
        $request->validate([
            'subject' => 'nullable|string',
        ]);

        try {
            $paper = $this->examService->getPYQPaper($examId, $year, $request->query('subject'));

            return response()->json([
                'success' => true,
                'data' => $paper,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'PYQ paper not found.',
            ], 404);
        }
    }

    /**
     * Start a PYQ mock test (full paper for a specific year).
     * POST /api/exams/pyq-mock-test/generate
     */
    public function generatePYQMockTest(Request $request): JsonResponse
    {
        $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'year' => 'required|integer|min:2018|max:2030',
            'subject' => 'nullable|string',
        ]);

        $user = $request->user();

        // Check exam prep usage
        $check = $this->usageLimitService->checkAndRecord($user, 'exam_prep');
        if (!$check['allowed']) {
            return response()->json([
                'success' => false,
                'message' => $check['reason'],
                'upgrade_required' => true,
            ], 429);
        }

        try {
            $mockTest = $this->examService->createPYQMockTest(
                $user,
                $request->exam_id,
                $request->year,
                $request->subject,
            );

            return response()->json([
                'success' => true,
                'message' => 'PYQ mock test created successfully.',
                'data' => $mockTest,
            ]);
        } catch (\Exception $e) {
            \Log::error('ExamController: generatePYQMockTest failed', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'exam_id' => $request->exam_id,
                'year' => $request->year,
            ]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
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
                'message' => $check['reason'],
                'upgrade_required' => true,
            ], 429);
        }

        try {
            // Extend execution time for AI question generation (reduced from 300)
            set_time_limit(120);

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
