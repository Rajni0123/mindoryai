<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AnswerBankService;
use App\Models\QuestionBank;
use App\Models\AnswerBankStats;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AnswerBankController extends Controller
{
    protected AnswerBankService $service;

    public function __construct(AnswerBankService $service)
    {
        $this->service = $service;
    }

    /**
     * POST /api/answer-bank/ask
     * Main endpoint - ask a question
     */
    public function ask(Request $request): JsonResponse
    {
        $request->validate([
            'question' => 'required|string|min:3|max:2000',
            'exam_type' => 'nullable|string|in:JEE,NEET,CBSE,UPSC',
        ]);

        $result = $this->service->getAnswer(
            $request->input('question'),
            [
                'exam_type' => $request->input('exam_type', 'JEE/NEET'),
            ]
        );

        return response()->json([
            'success' => $result['source'] !== 'error',
            'data' => [
                'answer' => $result['answer'],
                'source' => $result['source'], // 'cache' or 'api'
                'subject' => $result['subject'],
                'topic' => $result['topic'],
            ],
        ]);
    }

    /**
     * GET /api/answer-bank/stats
     * Dashboard stats
     */
    public function stats(): JsonResponse
    {
        $stats = AnswerBankStats::getSummary();

        return response()->json([
            'success' => true,
            'data' => array_merge($stats, [
                'total_questions' => QuestionBank::count(),
                'total_hits' => (int) QuestionBank::sum('hit_count'),
                'top_subjects' => QuestionBank::selectRaw('subject, COUNT(*) as count')
                    ->whereNotNull('subject')
                    ->groupBy('subject')
                    ->orderByDesc('count')
                    ->get(),
            ]),
        ]);
    }

    /**
     * GET /api/answer-bank/top
     * Top cached questions
     */
    public function topQuestions(Request $request): JsonResponse
    {
        $limit = min((int) $request->input('limit', 20), 100);

        $questions = QuestionBank::orderByDesc('hit_count')
            ->limit($limit)
            ->get(['id', 'original_question', 'subject', 'topic', 'hit_count', 'api_cost_saved', 'created_at']);

        return response()->json([
            'success' => true,
            'data' => $questions,
        ]);
    }

    /**
     * GET /api/answer-bank/search
     * Search the answer bank
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2',
            'subject' => 'nullable|string',
        ]);

        $query = QuestionBank::query();

        if ($subject = $request->input('subject')) {
            $query->where('subject', $subject);
        }

        $results = $query->whereRaw(
            "MATCH(original_question, normalized_question) AGAINST(? IN NATURAL LANGUAGE MODE)",
            [$request->input('q')]
        )
        ->selectRaw("*, MATCH(original_question, normalized_question) AGAINST(? IN NATURAL LANGUAGE MODE) as score", [$request->input('q')])
        ->orderByDesc('score')
        ->limit(10)
        ->get(['id', 'original_question', 'answer', 'subject', 'topic', 'hit_count']);

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }
}
