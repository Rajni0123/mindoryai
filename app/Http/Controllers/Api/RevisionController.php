<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LearningAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RevisionController extends Controller
{
    public function profile(Request $request): JsonResponse
    {
        $profile = LearningAnalyticsService::getRevisionProfile($request->user()->id);

        return response()->json($profile);
    }

    public function plan(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'plan' => LearningAnalyticsService::buildRevisionPlan($request->user()->id),
        ]);
    }

    public function flashcards(Request $request): JsonResponse
    {
        $subject = $request->query('subject');

        return response()->json([
            'success' => true,
            'cards' => LearningAnalyticsService::getFlashcards($request->user()->id, $subject),
        ]);
    }
}
