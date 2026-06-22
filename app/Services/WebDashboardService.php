<?php

namespace App\Services;

use App\Models\DailyChallenge;
use App\Models\DailyChallengeAttempt;
use App\Models\DynamicAppConfig;
use App\Models\Exam;
use App\Models\QuizAttempt;
use App\Models\User;
use Carbon\Carbon;

class WebDashboardService
{
    public function build(User $user): array
    {
        $userId = $user->id;

        $revisionProfile = LearningAnalyticsService::getRevisionProfile($userId);
        $dashboardData = LearningAnalyticsService::getDashboardData($userId);
        $revisionPlan = LearningAnalyticsService::buildRevisionPlan($userId);
        $quizStats = $this->weeklyQuizStats($userId);
        $usageSummary = app(UsageLimitService::class)->getUsageSummary($user);
        $dailyChallenge = $this->dailyChallengeData($user);
        $badges = $revisionProfile['badges'] ?? BadgeService::getUserBadges($userId);

        $planDays = collect($revisionPlan['days'] ?? []);
        $planCompleted = $planDays->where('completed', true)->count();
        $planTotal = max($planDays->count(), 1);
        $dailyProgress = (int) round(($planCompleted / $planTotal) * 100);

        $weakTopics = array_slice($revisionProfile['weak_topics'] ?? [], 0, 6);
        $weaknessMap = $this->buildWeaknessMap($weakTopics, $userId);

        $leaderboard = app(StudyBattleService::class)->getLeaderboard(7);
        $userRank = $this->findUserRank($leaderboard, $userId);

        $streak = max(
            (int) ($user->current_streak ?? 0),
            (int) ($revisionProfile['profile']['streak'] ?? 0),
            (int) ($quizStats['dayStreak'] ?? 0),
            (int) ($badges['stats']['streak'] ?? 0)
        );

        $level = (int) ($revisionProfile['profile']['level'] ?? $user->current_level ?? 1);
        $xp = (int) ($revisionProfile['profile']['total_xp'] ?? $user->total_xp ?? 0);
        $quizAccuracy = $quizStats['totalQuizzes'] > 0
            ? (int) $quizStats['averageScore']
            : (int) round($revisionProfile['overall_accuracy'] ?? 0);

        $continueLearning = $this->continueLearning($user, $revisionPlan, $weakTopics, $quizStats, (int) ($revisionProfile['strength_score'] ?? 0));
        $upcomingExam = $this->upcomingExam($user);
        $todayPlan = $this->todayPlanTasks($revisionPlan, $weakTopics, $quizStats);
        $coachNote = $this->coachNote($dailyProgress, $planCompleted, $planTotal, $planDays->isNotEmpty());

        $plan = $user->plan;
        $isFree = ! $plan || (float) ($plan->price_monthly ?? $plan->price ?? 0) <= 0;
        $mainUrl = rtrim((string) (config('domains.main_url') ?: config('app.url')), '/');

        $name = trim($user->name ?? '') ?: 'Student';
        $firstName = explode(' ', $name)[0];

        return [
            'success' => true,
            'user' => [
                'id' => $userId,
                'name' => $name,
                'first_name' => $firstName,
                'initial' => strtoupper(substr($name, 0, 1)),
                'target_exam' => $user->target_exam,
                'student_class' => $user->student_class,
            ],
            'greeting' => 'Hi, ' . $firstName . '!',
            'coach_note' => $coachNote,
            'streak' => $streak,
            'streak_label' => $streak === 1 ? '1 DAY STREAK' : $streak . ' DAY STREAK',
            'streak_badge' => $badges['primary']['streak'] ?? null,
            'level' => $level,
            'xp' => $xp,
            'level_progress' => $revisionProfile['level_progress'] ?? [],
            'accuracy' => (int) round($revisionProfile['overall_accuracy'] ?? 0),
            'quiz_accuracy' => $quizAccuracy,
            'strength_score' => (int) ($revisionProfile['strength_score'] ?? 0),
            'accuracy_rings' => [
                ['label' => 'Quizzes', 'value' => $quizAccuracy, 'color' => '#528dff'],
                ['label' => 'Strength', 'value' => (int) ($revisionProfile['strength_score'] ?? 0), 'color' => '#afc6ff'],
            ],
            'daily_progress' => $dailyProgress,
            'plan_completed' => $planCompleted,
            'plan_total' => $planTotal,
            'weak_topics' => $weakTopics,
            'weakness_map' => $weaknessMap['points'],
            'weakness_has_data' => $weaknessMap['has_data'],
            'weakness_message' => $weaknessMap['message'],
            'revision_plan' => $revisionPlan,
            'today_plan' => $todayPlan,
            'leaderboard' => $leaderboard,
            'user_leaderboard_rank' => $userRank,
            'daily_challenge' => $dailyChallenge,
            'continue_learning' => $continueLearning,
            'upcoming_exam' => $upcomingExam,
            'today_stats' => $dashboardData['today_stats'] ?? [],
            'quiz_stats' => $quizStats,
            'peer_comparison' => $revisionProfile['peer_comparison'] ?? [],
            'badges' => $badges,
            'usage' => $usageSummary,
            'is_free_plan' => $isFree,
            'plan_name' => $plan?->name ?? 'Free',
            'upgrade_url' => $mainUrl . '/pricing',
            'quick_actions' => $this->quickActions($isFree, $quizStats, $mainUrl),
            'messages' => [
                'revision_empty' => 'Take a quiz to unlock your personalised revision plan.',
                'leaderboard_empty' => 'Play study battles to climb the leaderboard.',
                'weakness_empty' => 'Complete quizzes to reveal your weakness map.',
            ],
        ];
    }

    private function weeklyQuizStats(int $userId): array
    {
        $start = now()->startOfWeek();
        $end = now()->endOfWeek();

        $attempts = QuizAttempt::where('user_id', $userId)
            ->completed()
            ->whereBetween('created_at', [$start, $end])
            ->get();

        $total = $attempts->count();
        $dayStreak = 0;
        $checkDate = now()->copy()->startOfDay();

        while ($dayStreak < 366) {
            $hasActivity = QuizAttempt::where('user_id', $userId)
                ->completed()
                ->whereDate('created_at', $checkDate)
                ->exists();

            if (! $hasActivity) {
                break;
            }

            $dayStreak++;
            $checkDate->subDay();
        }

        $totalSeconds = (int) $attempts->sum('time_taken_seconds');

        return [
            'totalQuizzes' => $total,
            'averageScore' => $total > 0 ? (int) round($attempts->avg('score')) : 0,
            'bestScore' => $total > 0 ? (int) round($attempts->max('score')) : 0,
            'totalQuestions' => (int) $attempts->sum('total_questions'),
            'totalTimeSeconds' => $totalSeconds,
            'studyHoursWeek' => round($totalSeconds / 3600, 1),
            'dayStreak' => $dayStreak,
        ];
    }

    private function dailyChallengeData(User $user): array
    {
        $enabled = DynamicAppConfig::getValue('features.daily_challenge_enabled', true);

        if (! $enabled) {
            return [
                'available' => false,
                'completed' => false,
                'title' => 'Daily Challenge',
                'description' => 'Daily challenges are currently unavailable.',
                'participants' => 0,
                'reward_xp' => 0,
            ];
        }

        $challenge = DailyChallenge::getToday();

        if (! $challenge) {
            return [
                'available' => false,
                'completed' => false,
                'title' => 'Daily Challenge',
                'description' => 'No challenge today. Check back tomorrow!',
                'participants' => 0,
                'reward_xp' => 0,
            ];
        }

        $attempt = DailyChallengeAttempt::where('user_id', $user->id)
            ->where('daily_challenge_id', $challenge->id)
            ->first();

        $participants = DailyChallengeAttempt::where('daily_challenge_id', $challenge->id)
            ->where('completed', true)
            ->count();

        $questionCount = is_array($challenge->questions) ? count($challenge->questions) : 10;
        $minutes = max(1, (int) floor(($challenge->time_limit_seconds ?? 600) / 60));
        $reward = (int) ($challenge->reward_credits ?? 50);

        return [
            'available' => true,
            'completed' => (bool) ($attempt?->completed),
            'title' => $challenge->title ?: 'Daily Challenge',
            'subject' => $challenge->subject,
            'description' => "Solve {$questionCount} questions in {$minutes} mins · Win {$reward} XP",
            'participants' => $participants,
            'reward_xp' => $reward,
            'score' => $attempt?->score,
        ];
    }

    private function continueLearning(User $user, array $revisionPlan, array $weakTopics, array $quizStats, int $readiness): array
    {
        $examName = $user->target_exam ?: '';
        $exam = Exam::query()->where('is_active', true)->orderBy('order')->first();
        $examId = $exam?->id;

        if ($examName === '' && $exam) {
            $examName = $exam->name;
        }
        if ($examName === '') {
            $examName = 'Your Exam';
        }

        $subject = 'General';
        $topic = 'Continue your prep';
        $topicsDone = min(25, (int) ($quizStats['totalQuestions'] ?? 0));
        $topicsTotal = 25;

        foreach ($revisionPlan['days'] ?? [] as $day) {
            if (empty($day['completed']) && ($day['action'] ?? '') !== 'mock_test') {
                $subject = $day['subject'] ?? $subject;
                $topic = $day['topic'] ?? $topic;
                break;
            }
        }

        if ($topic === 'Continue your prep' && ! empty($weakTopics)) {
            $subject = $weakTopics[0]['subject'] ?? $subject;
            $topic = $weakTopics[0]['topic'] ?? $topic;
        }

        $lastQuiz = QuizAttempt::where('user_id', $user->id)
            ->completed()
            ->latest()
            ->first();

        if ($lastQuiz) {
            $subject = $lastQuiz->subject ?: $subject;
            $topic = $lastQuiz->topic ?: $topic;
        }

        if ($readiness > 0) {
            $topicsDone = max($topicsDone, (int) round(($readiness / 100) * $topicsTotal));
        }

        return [
            'exam_name' => $examName,
            'exam_id' => $examId,
            'subject' => $subject,
            'topic' => $topic,
            'topics_done' => min($topicsDone, $topicsTotal),
            'topics_total' => $topicsTotal,
            'progress_percent' => (int) round(min(100, ($topicsDone / max(1, $topicsTotal)) * 100)),
        ];
    }

    private function upcomingExam(User $user): array
    {
        $examName = $user->target_exam ?: 'Your Exam';
        $exam = Exam::query()->where('is_active', true)->orderBy('order')->first();

        if ($exam && ($examName === 'Your Exam' || $examName === '')) {
            $examName = $exam->name;
        }

        $rawDate = $user->exam_date;
        $daysLeft = null;
        $dateLabel = null;

        if ($rawDate) {
            $dt = Carbon::parse($rawDate);
            $daysLeft = max(0, now()->startOfDay()->diffInDays($dt->startOfDay(), false));
            $dateLabel = $dt->format('d M Y');
        }

        return [
            'exam_name' => $examName,
            'days_left' => $daysLeft,
            'exam_date' => $dateLabel,
            'has_date' => $daysLeft !== null,
        ];
    }

    private function todayPlanTasks(array $revisionPlan, array $weakTopics, array $quizStats): array
    {
        $tasks = [];

        foreach ($revisionPlan['days'] ?? [] as $day) {
            if (($day['action'] ?? '') === 'mock_test') {
                continue;
            }

            $action = $day['action'] ?? 'revise';
            $title = match ($action) {
                'quiz' => 'Practice ' . ($day['topic'] ?? 'topic'),
                'revision' => 'Revision: ' . ($day['topic'] ?? 'topic'),
                default => 'Revise ' . ($day['topic'] ?? 'topic'),
            };

            $rate = isset($day['success_rate']) ? round((float) $day['success_rate']) . '% accuracy · ' : '';
            $tasks[] = [
                'day' => $day['day'] ?? count($tasks) + 1,
                'title' => $title,
                'topic' => $day['topic'] ?? '',
                'subject' => $day['subject'] ?? 'General',
                'action' => $action,
                'subtitle' => ($day['completed'] ?? false)
                    ? 'Completed'
                    : $rate . '15 min session',
                'completed' => (bool) ($day['completed'] ?? false),
            ];

            if (count($tasks) >= 5) {
                break;
            }
        }

        if ($tasks !== []) {
            return $tasks;
        }

        if (! empty($weakTopics)) {
            $w = $weakTopics[0];
            $acc = (int) round($w['success_rate'] ?? ($w['accuracy'] ?? 0) * 100);
            $tasks[] = [
                'day' => 1,
                'title' => 'Revise ' . ($w['topic'] ?? 'weak topic'),
                'topic' => $w['topic'] ?? '',
                'subject' => $w['subject'] ?? 'General',
                'action' => 'revise',
                'subtitle' => "{$acc}% accuracy · 15 min",
                'completed' => $acc >= 70,
            ];
        }

        $questions = (int) ($quizStats['totalQuestions'] ?? 0);
        $tasks[] = [
            'day' => count($tasks) + 1,
            'title' => 'Solve 10 Questions',
            'topic' => '',
            'subject' => 'Practice',
            'action' => 'quiz',
            'subtitle' => "{$questions} / 10 this week",
            'completed' => $questions >= 10,
        ];

        return $tasks;
    }

    private function buildWeaknessMap(array $weakTopics, int $userId): array
    {
        if ($weakTopics !== []) {
            $points = collect($weakTopics)->take(5)->map(function ($t) {
                $label = $t['topic'] ?? $t['subject'] ?? 'Topic';
                $words = explode(' ', trim($label));
                $short = count($words) > 2 ? implode(' ', array_slice($words, 0, 2)) : $label;
                $rate = $t['success_rate'] ?? (($t['accuracy'] ?? 0.5) * 100);

                return [
                    'label' => $short,
                    'value' => max(0.15, min(1, ((float) $rate) / 100)),
                ];
            })->values()->all();

            return [
                'has_data' => true,
                'points' => $points,
                'message' => null,
            ];
        }

        return [
            'has_data' => false,
            'points' => [],
            'message' => 'Complete quizzes to reveal your weakness map.',
        ];
    }

    private function coachNote(int $progress, int $completed, int $total, bool $hasPlan): string
    {
        if (! $hasPlan && $total === 0) {
            return 'Complete a session to unlock your personalised daily plan.';
        }

        if ($total > 0 && $completed >= $total) {
            return 'Session complete — great discipline today!';
        }

        if ($progress >= 75) {
            return 'Almost there — finish strong today.';
        }

        if ($progress >= 40) {
            return 'Good momentum. Keep your streak alive.';
        }

        return 'Start with one task — small wins compound.';
    }

    private function quickActions(bool $isFree, array $quizStats, string $mainUrl): array
    {
        $actions = [
            [
                'id' => 'ai_tutor',
                'label' => 'AI Tutor',
                'icon' => 'smart_toy',
                'action' => 'chat',
                'subtitle' => null,
            ],
            [
                'id' => 'mock_test',
                'label' => 'Mock Test',
                'icon' => 'quiz',
                'action' => 'mock_test',
                'subtitle' => ($quizStats['totalQuizzes'] ?? 0) . ' this week',
            ],
            [
                'id' => 'battle',
                'label' => 'Battle',
                'icon' => 'bolt',
                'action' => 'battle',
                'subtitle' => 'Live quiz duels',
            ],
            [
                'id' => 'revision',
                'label' => 'Revision',
                'icon' => 'menu_book',
                'action' => 'revision',
                'subtitle' => null,
            ],
        ];

        if ($isFree) {
            $actions[] = [
                'id' => 'upgrade',
                'label' => 'Upgrade',
                'icon' => 'workspace_premium',
                'action' => 'upgrade',
                'url' => $mainUrl . '/pricing',
                'subtitle' => null,
            ];
        }

        return $actions;
    }

    private function findUserRank(array $leaderboard, int $userId): ?int
    {
        foreach ($leaderboard as $entry) {
            if ((int) ($entry['user_id'] ?? 0) === $userId) {
                return (int) ($entry['rank'] ?? null);
            }
        }

        return null;
    }
}
