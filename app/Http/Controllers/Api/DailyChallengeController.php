<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DailyChallenge;
use App\Models\DailyChallengeAttempt;
use App\Models\DynamicAppConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DailyChallengeController extends Controller
{
    /**
     * Get today's daily challenge
     * GET /api/daily-challenge/today
     */
    public function getToday(Request $request): JsonResponse
    {
        $user = $request->user();

        // Check if feature is enabled
        $enabled = DynamicAppConfig::getValue('features.daily_challenge_enabled', true);
        if (!$enabled) {
            return response()->json([
                'success' => false,
                'message' => 'Daily challenges are currently disabled.',
                'available' => false,
            ]);
        }

        $challenge = DailyChallenge::getToday();

        if (!$challenge) {
            return response()->json([
                'success' => true,
                'available' => false,
                'message' => 'No challenge available today. Check back tomorrow!',
            ]);
        }

        // Check if user has already attempted today's challenge
        $attempt = null;
        if ($user) {
            $attempt = DailyChallengeAttempt::where('user_id', $user->id)
                ->where('daily_challenge_id', $challenge->id)
                ->first();
        }

        // Get user's streak
        $streak = $this->getUserStreak($user);

        // Get leaderboard preview (top 5)
        $topParticipants = DailyChallengeAttempt::where('daily_challenge_id', $challenge->id)
            ->where('completed', true)
            ->orderByDesc('score')
            ->orderBy('time_taken_seconds')
            ->with(['user:id,name,plan_id', 'user.userPlan:id,name,slug'])
            ->limit(5)
            ->get()
            ->map(function ($a, $index) {
                $plan = $a->user->userPlan ?? null;
                return [
                    'rank' => $index + 1,
                    'user_id' => $a->user->id ?? null,
                    'name' => $a->user->name ?? 'Anonymous',
                    'score' => $a->score,
                    'time' => $a->time_taken_seconds,
                    'plan' => $plan ? [
                        'name' => $plan->name,
                        'slug' => $plan->slug,
                    ] : ['name' => null, 'slug' => null],
                ];
            });

        return response()->json([
            'success' => true,
            'available' => true,
            'challenge' => [
                'id' => $challenge->id,
                'title' => $challenge->title,
                'subject' => $challenge->subject,
                'difficulty' => $challenge->difficulty,
                'question_count' => count($challenge->questions),
                'time_limit_seconds' => $challenge->time_limit_seconds,
                'reward_credits' => $challenge->reward_credits,
                'participants_count' => $challenge->getParticipantsCount(),
                'average_score' => round($challenge->getAverageScore(), 1),
            ],
            'user_attempt' => $attempt ? [
                'completed' => $attempt->completed,
                'score' => $attempt->score,
                'total_questions' => $attempt->total_questions,
                'time_taken' => $attempt->time_taken_seconds,
                'credits_earned' => $attempt->credits_earned,
            ] : null,
            'streak' => $streak,
            'leaderboard' => $topParticipants,
        ]);
    }

    /**
     * Start the daily challenge
     * POST /api/daily-challenge/start
     */
    public function start(Request $request): JsonResponse
    {
        $user = $request->user();
        $challenge = DailyChallenge::getToday();

        if (!$challenge) {
            return response()->json([
                'success' => false,
                'message' => 'No challenge available today.',
            ], 404);
        }

        // Check if user already completed
        $existingAttempt = DailyChallengeAttempt::where('user_id', $user->id)
            ->where('daily_challenge_id', $challenge->id)
            ->first();

        if ($existingAttempt && $existingAttempt->completed) {
            return response()->json([
                'success' => false,
                'message' => 'You have already completed today\'s challenge!',
                'already_completed' => true,
                'score' => $existingAttempt->score,
            ], 400);
        }

        // Create or update attempt
        if (!$existingAttempt) {
            $existingAttempt = DailyChallengeAttempt::create([
                'user_id' => $user->id,
                'daily_challenge_id' => $challenge->id,
                'total_questions' => count($challenge->questions),
            ]);
        }

        // Return questions without correct answers
        $questions = collect($challenge->questions)->map(function ($q, $index) {
            return [
                'index' => $index,
                'question' => $q['question'],
                'options' => $q['options'],
            ];
        });

        return response()->json([
            'success' => true,
            'attempt_id' => $existingAttempt->id,
            'challenge' => [
                'id' => $challenge->id,
                'title' => $challenge->title,
                'subject' => $challenge->subject,
                'difficulty' => $challenge->difficulty,
                'time_limit_seconds' => $challenge->time_limit_seconds,
                'reward_credits' => $challenge->reward_credits,
            ],
            'questions' => $questions,
            'total_questions' => count($challenge->questions),
        ]);
    }

    /**
     * Submit the daily challenge
     * POST /api/daily-challenge/submit
     */
    public function submit(Request $request): JsonResponse
    {
        $request->validate([
            'answers' => 'required|array',
            'time_taken_seconds' => 'required|integer|min:0',
        ]);

        $user = $request->user();
        $challenge = DailyChallenge::getToday();

        if (!$challenge) {
            return response()->json([
                'success' => false,
                'message' => 'No challenge available.',
            ], 404);
        }

        $attempt = DailyChallengeAttempt::where('user_id', $user->id)
            ->where('daily_challenge_id', $challenge->id)
            ->first();

        if (!$attempt) {
            return response()->json([
                'success' => false,
                'message' => 'You must start the challenge first.',
            ], 400);
        }

        if ($attempt->completed) {
            return response()->json([
                'success' => false,
                'message' => 'You have already submitted this challenge.',
                'score' => $attempt->score,
            ], 400);
        }

        // Calculate score
        $answers = $request->answers;
        $questions = $challenge->questions;
        $score = 0;
        $results = [];

        foreach ($questions as $index => $question) {
            $userAnswer = $answers[$index] ?? null;
            $correctAnswer = $question['correct_answer'];
            $isCorrect = $userAnswer !== null && (int) $userAnswer === (int) $correctAnswer;

            if ($isCorrect) {
                $score++;
            }

            $results[] = [
                'index' => $index,
                'question' => $question['question'],
                'options' => $question['options'],
                'user_answer' => $userAnswer,
                'correct_answer' => $correctAnswer,
                'is_correct' => $isCorrect,
                'explanation' => $question['explanation'] ?? '',
            ];
        }

        // Calculate credits
        $creditsEarned = 0;
        $totalQuestions = count($questions);
        $scorePercentage = ($score / $totalQuestions) * 100;

        // Base credits for completion
        if ($scorePercentage >= 50) {
            $creditsEarned = $challenge->reward_credits;
        } elseif ($scorePercentage >= 30) {
            $creditsEarned = (int) ($challenge->reward_credits * 0.5);
        }

        // Streak bonus
        $streak = $this->getUserStreak($user);
        if ($streak >= 7) {
            $streakBonus = DynamicAppConfig::getValue('daily_challenge.streak_bonus_credits', 5);
            $creditsEarned += $streakBonus;
        }

        // Update attempt
        $attempt->update([
            'score' => $score,
            'time_taken_seconds' => $request->time_taken_seconds,
            'answers' => $answers,
            'completed' => true,
            'credits_earned' => $creditsEarned,
        ]);

        // Add credits to user
        if ($creditsEarned > 0 && method_exists($user, 'addCredits')) {
            $user->addCredits($creditsEarned, 'daily_challenge', "Daily Challenge: {$challenge->title}");
        }

        // Get user rank
        $userRank = DailyChallengeAttempt::where('daily_challenge_id', $challenge->id)
            ->where('completed', true)
            ->where(function ($q) use ($score, $request) {
                $q->where('score', '>', $score)
                    ->orWhere(function ($q2) use ($score, $request) {
                        $q2->where('score', $score)
                            ->where('time_taken_seconds', '<', $request->time_taken_seconds);
                    });
            })
            ->count() + 1;

        return response()->json([
            'success' => true,
            'message' => 'Challenge completed!',
            'result' => [
                'score' => $score,
                'total_questions' => $totalQuestions,
                'percentage' => round($scorePercentage, 1),
                'time_taken_seconds' => $request->time_taken_seconds,
                'credits_earned' => $creditsEarned,
                'rank' => $userRank,
                'streak' => $streak + 1,
            ],
            'results' => $results,
        ]);
    }

    /**
     * Get leaderboard for today's challenge
     * GET /api/daily-challenge/leaderboard
     */
    public function leaderboard(Request $request): JsonResponse
    {
        $challenge = DailyChallenge::getToday();

        if (!$challenge) {
            return response()->json([
                'success' => false,
                'message' => 'No challenge available today.',
            ], 404);
        }

        $leaderboard = DailyChallengeAttempt::where('daily_challenge_id', $challenge->id)
            ->where('completed', true)
            ->orderByDesc('score')
            ->orderBy('time_taken_seconds')
            ->with(['user:id,name,plan_id', 'user.userPlan:id,name,slug'])
            ->limit(50)
            ->get()
            ->map(function ($a, $index) {
                $plan = $a->user->userPlan ?? null;
                return [
                    'rank' => $index + 1,
                    'user_id' => $a->user->id ?? null,
                    'name' => $a->user->name ?? 'Anonymous',
                    'score' => $a->score,
                    'time' => $a->time_taken_seconds,
                    'formatted_time' => gmdate('i:s', $a->time_taken_seconds),
                    'plan' => $plan ? [
                        'name' => $plan->name,
                        'slug' => $plan->slug,
                    ] : ['name' => null, 'slug' => null],
                ];
            });

        // Get user's rank
        $user = $request->user();
        $userRank = null;
        if ($user) {
            $attempt = DailyChallengeAttempt::where('user_id', $user->id)
                ->where('daily_challenge_id', $challenge->id)
                ->where('completed', true)
                ->first();

            if ($attempt) {
                $userRank = DailyChallengeAttempt::where('daily_challenge_id', $challenge->id)
                    ->where('completed', true)
                    ->where(function ($q) use ($attempt) {
                        $q->where('score', '>', $attempt->score)
                            ->orWhere(function ($q2) use ($attempt) {
                                $q2->where('score', $attempt->score)
                                    ->where('time_taken_seconds', '<', $attempt->time_taken_seconds);
                            });
                    })
                    ->count() + 1;
            }
        }

        return response()->json([
            'success' => true,
            'challenge' => [
                'id' => $challenge->id,
                'title' => $challenge->title,
                'participants' => $challenge->getParticipantsCount(),
            ],
            'leaderboard' => $leaderboard,
            'user_rank' => $userRank,
        ]);
    }

    /**
     * Get user's challenge history
     * GET /api/daily-challenge/history
     */
    public function history(Request $request): JsonResponse
    {
        $user = $request->user();

        $history = DailyChallengeAttempt::where('user_id', $user->id)
            ->where('completed', true)
            ->with('dailyChallenge:id,title,subject,challenge_date')
            ->orderByDesc('created_at')
            ->limit(30)
            ->get()
            ->map(fn($a) => [
                'date' => $a->dailyChallenge->challenge_date->format('Y-m-d'),
                'title' => $a->dailyChallenge->title,
                'subject' => $a->dailyChallenge->subject,
                'score' => $a->score,
                'total' => $a->total_questions,
                'time' => $a->time_taken_seconds,
                'credits' => $a->credits_earned,
            ]);

        return response()->json([
            'success' => true,
            'history' => $history,
            'streak' => $this->getUserStreak($user),
            'total_completed' => $history->count(),
        ]);
    }

    /**
     * Calculate user's current streak
     */
    private function getUserStreak($user): int
    {
        if (!$user) return 0;

        $streak = 0;
        $date = today();

        // Check backwards from today
        while (true) {
            $challenge = DailyChallenge::where('challenge_date', $date)
                ->where('is_active', true)
                ->first();

            if (!$challenge) {
                // No challenge on this day, skip but don't break streak
                $date = $date->subDay();
                if ($date < today()->subDays(30)) break;
                continue;
            }

            $attempt = DailyChallengeAttempt::where('user_id', $user->id)
                ->where('daily_challenge_id', $challenge->id)
                ->where('completed', true)
                ->exists();

            if ($attempt) {
                $streak++;
                $date = $date->subDay();
            } else {
                // If it's today and not completed yet, don't break
                if ($date->isToday()) {
                    $date = $date->subDay();
                    continue;
                }
                break;
            }
        }

        return $streak;
    }

    /**
     * Get weekly leaderboard (aggregated scores from past 7 days)
     * GET /api/daily-challenge/weekly-leaderboard
     */
    public function weeklyLeaderboard(Request $request): JsonResponse
    {
        $startDate = now()->subDays(7)->startOfDay();
        $endDate = now()->endOfDay();

        // Get all challenges from the past week
        $challengeIds = DailyChallenge::whereBetween('challenge_date', [$startDate, $endDate])
            ->where('is_active', true)
            ->pluck('id');

        if ($challengeIds->isEmpty()) {
            return response()->json([
                'success' => true,
                'leaderboard' => [],
                'user_rank' => null,
                'period' => [
                    'start' => $startDate->format('M d'),
                    'end' => $endDate->format('M d'),
                ],
            ]);
        }

        // Aggregate scores by user for the week
        $leaderboard = DailyChallengeAttempt::whereIn('daily_challenge_id', $challengeIds)
            ->where('completed', true)
            ->selectRaw('user_id, SUM(score) as total_score, COUNT(*) as challenges_completed, SUM(time_taken_seconds) as total_time')
            ->groupBy('user_id')
            ->orderByDesc('total_score')
            ->orderBy('total_time')
            ->with(['user:id,name,gender,plan_id', 'user.userPlan:id,name,slug'])
            ->limit(50)
            ->get()
            ->map(function ($entry, $index) {
                $user = $entry->user;
                $plan = $user->userPlan ?? null;
                return [
                    'rank' => $index + 1,
                    'user_id' => $user->id ?? null,
                    'name' => $user->name ?? 'Anonymous',
                    'gender' => $user->gender ?? null,
                    'score' => (int) $entry->total_score,
                    'challenges_completed' => (int) $entry->challenges_completed,
                    'time' => (int) $entry->total_time,
                    'formatted_time' => gmdate('H:i:s', $entry->total_time),
                    'plan' => $plan ? [
                        'name' => $plan->name,
                        'slug' => $plan->slug,
                    ] : ['name' => null, 'slug' => null],
                ];
            });

        // Get user's weekly rank
        $user = $request->user();
        $userRank = null;
        $userStats = null;

        if ($user) {
            $userWeeklyStats = DailyChallengeAttempt::whereIn('daily_challenge_id', $challengeIds)
                ->where('user_id', $user->id)
                ->where('completed', true)
                ->selectRaw('SUM(score) as total_score, COUNT(*) as challenges_completed, SUM(time_taken_seconds) as total_time')
                ->first();

            if ($userWeeklyStats && $userWeeklyStats->total_score > 0) {
                $userStats = [
                    'score' => (int) $userWeeklyStats->total_score,
                    'challenges_completed' => (int) $userWeeklyStats->challenges_completed,
                    'time' => (int) $userWeeklyStats->total_time,
                ];

                // Calculate rank
                $userRank = DailyChallengeAttempt::whereIn('daily_challenge_id', $challengeIds)
                    ->where('completed', true)
                    ->selectRaw('user_id, SUM(score) as total_score, SUM(time_taken_seconds) as total_time')
                    ->groupBy('user_id')
                    ->havingRaw('SUM(score) > ? OR (SUM(score) = ? AND SUM(time_taken_seconds) < ?)', [
                        $userWeeklyStats->total_score,
                        $userWeeklyStats->total_score,
                        $userWeeklyStats->total_time
                    ])
                    ->count() + 1;
            }
        }

        return response()->json([
            'success' => true,
            'leaderboard' => $leaderboard,
            'user_rank' => $userRank,
            'user_stats' => $userStats,
            'period' => [
                'start' => $startDate->format('M d'),
                'end' => $endDate->format('M d'),
            ],
            'total_participants' => $leaderboard->count(),
        ]);
    }
}
