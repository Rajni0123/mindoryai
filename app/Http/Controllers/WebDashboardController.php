<?php

namespace App\Http\Controllers;

use App\Models\QuizAttempt;
use App\Services\LearningAnalyticsService;
use App\Services\StudyBattleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebDashboardController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $userId = $user->id;

        $revisionProfile = LearningAnalyticsService::getRevisionProfile($userId);
        $dashboardData = LearningAnalyticsService::getDashboardData($userId);
        $revisionPlan = LearningAnalyticsService::buildRevisionPlan($userId);

        $leaderboard = app(StudyBattleService::class)->getLeaderboard(7);

        $planDays = collect($revisionPlan['days'] ?? []);
        $planCompleted = $planDays->where('completed', true)->count();
        $planTotal = max($planDays->count(), 1);

        $weeklyAttempts = QuizAttempt::where('user_id', $userId)
            ->completed()
            ->where('created_at', '>=', now()->startOfWeek())
            ->get();

        $quizAccuracy = $weeklyAttempts->count() > 0
            ? (int) round($weeklyAttempts->avg('score'))
            : (int) round($revisionProfile['overall_accuracy'] ?? 0);

        $name = trim($user->name ?? '') ?: 'Student';
        $firstName = explode(' ', $name)[0];

        return response()->json([
            'success' => true,
            'user' => [
                'name' => $name,
                'first_name' => $firstName,
                'target_exam' => $user->target_exam,
                'student_class' => $user->student_class,
            ],
            'streak' => (int) ($user->current_streak ?? $revisionProfile['profile']['streak'] ?? 0),
            'level' => (int) ($revisionProfile['profile']['level'] ?? 1),
            'xp' => (int) ($revisionProfile['profile']['total_xp'] ?? 0),
            'level_progress' => $revisionProfile['level_progress'] ?? [],
            'accuracy' => (int) round($revisionProfile['overall_accuracy'] ?? 0),
            'quiz_accuracy' => $quizAccuracy,
            'strength_score' => (int) ($revisionProfile['strength_score'] ?? 0),
            'weak_topics' => array_slice($revisionProfile['weak_topics'] ?? [], 0, 6),
            'revision_plan' => $revisionPlan,
            'plan_completed' => $planCompleted,
            'plan_total' => $planTotal,
            'daily_progress' => (int) round(($planCompleted / $planTotal) * 100),
            'badges' => $revisionProfile['badges'] ?? [],
            'leaderboard' => $leaderboard,
            'today_stats' => $dashboardData['today_stats'] ?? [],
            'peer_comparison' => $revisionProfile['peer_comparison'] ?? [],
        ]);
    }
}
