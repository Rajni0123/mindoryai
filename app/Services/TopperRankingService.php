<?php

namespace App\Services;

use App\Models\User;
use App\Models\PublicDoubt;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * TopperRankingService - Dynamic Topper System
 *
 * Automatically promotes/demotes users to topper status based on their performance.
 * Only top 10 users can be toppers at any time.
 *
 * Scoring Algorithm:
 * - Doubts Solved: +10 points each
 * - Average Rating: ×20 multiplier (e.g., 4.5 rating = 90 points)
 * - Fast Response Bonus: +50 points if avg response < 5 min
 * - Activity Streak: +5 points per consecutive active day (max 7 days = 35 points)
 * - Recent Activity: +30 points if active in last 24 hours
 */
class TopperRankingService
{
    // Maximum number of toppers allowed
    const MAX_TOPPERS = 10;

    // Minimum score required to be considered for topper
    const MIN_TOPPER_SCORE = 50;

    // Minimum doubts solved to be eligible
    const MIN_DOUBTS_SOLVED = 3;

    /**
     * Recalculate all user scores and update topper rankings
     */
    public function recalculateAllRankings(): array
    {
        Log::info('[TOPPER RANKING] Starting full recalculation...');

        $startTime = microtime(true);
        $stats = [
            'users_processed' => 0,
            'new_toppers' => 0,
            'demoted' => 0,
            'top_10' => [],
        ];

        try {
            // Step 1: Calculate scores for all active users
            $users = User::where('is_active', true)
                ->whereNotNull('name')
                ->get();

            foreach ($users as $user) {
                $score = $this->calculateUserScore($user);
                $user->update([
                    'topper_score' => $score,
                    'last_topper_calculation' => now(),
                ]);
                $stats['users_processed']++;
            }

            // Step 2: Get top 10 users by score
            $topUsers = User::where('is_active', true)
                ->where('topper_score', '>=', self::MIN_TOPPER_SCORE)
                ->where('doubts_solved_count', '>=', self::MIN_DOUBTS_SOLVED)
                ->orderByDesc('topper_score')
                ->limit(self::MAX_TOPPERS)
                ->get();

            $topUserIds = $topUsers->pluck('id')->toArray();

            // Step 3: Demote current toppers who are not in top 10
            $demoted = User::where('is_topper', true)
                ->whereNotIn('id', $topUserIds)
                ->update([
                    'is_topper' => false,
                    'is_verified_topper' => false,
                    'topper_available' => false,
                    'topper_rank_position' => null,
                ]);
            $stats['demoted'] = $demoted;

            // Step 4: Promote top 10 users to topper status
            $position = 1;
            foreach ($topUsers as $user) {
                $wasTopper = $user->is_topper;

                $user->update([
                    'is_topper' => true,
                    'is_verified_topper' => true, // Auto-verify top performers
                    'topper_rank_position' => $position,
                ]);

                if (!$wasTopper) {
                    $stats['new_toppers']++;
                    Log::info("[TOPPER RANKING] New topper: {$user->name} (Score: {$user->topper_score})");
                }

                $stats['top_10'][] = [
                    'rank' => $position,
                    'id' => $user->id,
                    'name' => $user->name,
                    'score' => $user->topper_score,
                    'doubts_solved' => $user->doubts_solved_count,
                ];

                $position++;
            }

            $duration = round(microtime(true) - $startTime, 2);
            Log::info("[TOPPER RANKING] Completed in {$duration}s. Processed: {$stats['users_processed']}, New: {$stats['new_toppers']}, Demoted: {$stats['demoted']}");

            return $stats;

        } catch (\Exception $e) {
            Log::error('[TOPPER RANKING] Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Calculate topper score for a single user
     */
    public function calculateUserScore(User $user): float
    {
        $score = 0;

        // 1. Doubts Solved Score (+10 points each)
        $doubtsSolved = $this->countDoubtsSolved($user);
        $user->doubts_solved_count = $doubtsSolved;
        $score += $doubtsSolved * 10;

        // 2. Rating Score (rating × 20)
        $avgRating = $this->getAverageRating($user);
        if ($avgRating > 0) {
            $score += $avgRating * 20;
        }

        // 3. Fast Response Bonus
        $avgResponseTime = $this->getAverageResponseTime($user);
        $user->avg_response_time_seconds = $avgResponseTime;
        if ($avgResponseTime > 0 && $avgResponseTime < 300) { // < 5 minutes
            $score += 50;
        } elseif ($avgResponseTime > 0 && $avgResponseTime < 600) { // < 10 minutes
            $score += 25;
        }

        // 4. Activity Streak Bonus (+5 per day, max 7 days = 35)
        $streakDays = $this->getActivityStreak($user);
        $score += min($streakDays, 7) * 5;

        // 5. Recent Activity Bonus (+30 if active in last 24h)
        if ($this->isRecentlyActive($user)) {
            $score += 30;
        }

        // 6. Quiz Performance Bonus (if they also help with quizzes)
        $quizScore = $this->getQuizContributionScore($user);
        $score += $quizScore;

        return round($score, 2);
    }

    /**
     * Count total doubts solved by user (as topper in conversations)
     */
    protected function countDoubtsSolved(User $user): int
    {
        // Count resolved conversations where user was the topper
        $conversationCount = ChatConversation::where('topper_id', $user->id)
            ->where('status', 'resolved')
            ->count();

        // Count public doubts answered
        $publicDoubtsCount = PublicDoubt::where('topper_id', $user->id)
            ->where('is_approved', true)
            ->count();

        // Also count helpful answers in AI chat (if user helped others)
        // This could be from a community Q&A feature if exists

        return $conversationCount + $publicDoubtsCount;
    }

    /**
     * Get average rating from students
     */
    protected function getAverageRating(User $user): float
    {
        $avgRating = ChatConversation::where('topper_id', $user->id)
            ->where('status', 'resolved')
            ->whereNotNull('student_rating')
            ->avg('student_rating');

        return $avgRating ?: 0;
    }

    /**
     * Get average response time in seconds
     */
    protected function getAverageResponseTime(User $user): int
    {
        // Calculate average time between student message and topper response
        $conversations = ChatConversation::where('topper_id', $user->id)
            ->where('status', 'resolved')
            ->with(['messages' => function ($q) {
                $q->orderBy('created_at');
            }])
            ->limit(20) // Last 20 conversations
            ->get();

        $totalResponseTime = 0;
        $responseCount = 0;

        foreach ($conversations as $conversation) {
            $messages = $conversation->messages;
            $lastStudentMessage = null;

            foreach ($messages as $message) {
                if ($message->sender_type === 'student') {
                    $lastStudentMessage = $message;
                } elseif ($message->sender_type === 'topper' && $lastStudentMessage) {
                    $responseTime = Carbon::parse($message->created_at)
                        ->diffInSeconds(Carbon::parse($lastStudentMessage->created_at));
                    $totalResponseTime += $responseTime;
                    $responseCount++;
                    $lastStudentMessage = null;
                }
            }
        }

        return $responseCount > 0 ? intval($totalResponseTime / $responseCount) : 0;
    }

    /**
     * Get number of consecutive active days
     */
    protected function getActivityStreak(User $user): int
    {
        $streak = 0;
        $currentDate = Carbon::today();

        for ($i = 0; $i < 30; $i++) { // Check last 30 days
            $date = $currentDate->copy()->subDays($i);

            $hasActivity = ChatMessage::where('sender_id', $user->id)
                ->whereDate('created_at', $date)
                ->exists();

            if ($hasActivity) {
                $streak++;
            } else {
                break; // Streak broken
            }
        }

        return $streak;
    }

    /**
     * Check if user was active in last 24 hours
     */
    protected function isRecentlyActive(User $user): bool
    {
        return ChatMessage::where('sender_id', $user->id)
            ->where('created_at', '>=', now()->subDay())
            ->exists();
    }

    /**
     * Get quiz contribution score (answering quiz questions, etc.)
     */
    protected function getQuizContributionScore(User $user): float
    {
        // If user has high quiz scores, they might be good at helping others
        // This is optional - give bonus for users with good quiz performance
        $avgQuizScore = DB::table('quiz_attempts')
            ->where('user_id', $user->id)
            ->avg('score');

        if ($avgQuizScore && $avgQuizScore >= 80) {
            return 20; // Bonus for high performers
        } elseif ($avgQuizScore && $avgQuizScore >= 60) {
            return 10;
        }

        return 0;
    }

    /**
     * Get current leaderboard (top 10 toppers)
     */
    public function getLeaderboard(): array
    {
        return User::where('is_topper', true)
            ->where('is_verified_topper', true)
            ->orderBy('topper_rank_position')
            ->limit(self::MAX_TOPPERS)
            ->get()
            ->map(fn($user) => [
                'rank' => $user->topper_rank_position,
                'id' => $user->id,
                'name' => $user->name,
                'score' => $user->topper_score,
                'doubts_solved' => $user->doubts_solved_count,
                'rating' => $user->topper_rating,
                'response_time' => $user->avg_response_time_seconds,
                'is_available' => $user->topper_available,
            ])
            ->toArray();
    }

    /**
     * Check if a specific user qualifies for topper status
     */
    public function checkUserEligibility(User $user): array
    {
        $score = $this->calculateUserScore($user);
        $user->update(['topper_score' => $score]);

        // Get current lowest topper score
        $lowestTopper = User::where('is_topper', true)
            ->where('is_verified_topper', true)
            ->orderBy('topper_score')
            ->first();

        $lowestScore = $lowestTopper ? $lowestTopper->topper_score : 0;

        // Count current toppers
        $currentTopperCount = User::where('is_topper', true)
            ->where('is_verified_topper', true)
            ->count();

        $isEligible = $score >= self::MIN_TOPPER_SCORE &&
                      $user->doubts_solved_count >= self::MIN_DOUBTS_SOLVED;

        $canBePromoted = $isEligible && (
            $currentTopperCount < self::MAX_TOPPERS ||
            $score > $lowestScore
        );

        return [
            'user_id' => $user->id,
            'current_score' => $score,
            'doubts_solved' => $user->doubts_solved_count,
            'min_required_score' => self::MIN_TOPPER_SCORE,
            'min_doubts_required' => self::MIN_DOUBTS_SOLVED,
            'is_eligible' => $isEligible,
            'can_be_promoted' => $canBePromoted,
            'lowest_topper_score' => $lowestScore,
            'score_needed_to_beat' => max(0, $lowestScore - $score + 1),
            'is_currently_topper' => $user->is_topper,
        ];
    }

    /**
     * Trigger recalculation for a single user and update rankings if needed
     */
    public function updateUserRanking(User $user): array
    {
        $oldScore = $user->topper_score;
        $newScore = $this->calculateUserScore($user);

        $user->update([
            'topper_score' => $newScore,
            'last_topper_calculation' => now(),
        ]);

        // If score improved significantly, trigger full recalculation
        if ($newScore > $oldScore + 10) {
            $this->recalculateAllRankings();
        }

        return [
            'old_score' => $oldScore,
            'new_score' => $newScore,
            'change' => $newScore - $oldScore,
            'is_topper' => $user->fresh()->is_topper,
            'rank' => $user->fresh()->topper_rank_position,
        ];
    }
}
