<?php

namespace App\Services;

use App\Models\DailyChallengeAttempt;
use App\Models\QuizAttempt;
use App\Models\StudyBattleParticipant;
use App\Models\User;
use App\Models\UserAchievement;

class BadgeService
{
    /**
     * Full badge payload for API / mobile app.
     */
    public static function getUserBadges(int $userId): array
    {
        $stats = self::collectStats($userId);
        $achievementKeys = UserAchievement::where('user_id', $userId)
            ->pluck('achievement_key')
            ->all();

        $league = self::pickTier('league', max(1, $stats['level']));
        $streak = $stats['streak'] > 0
            ? self::pickTier('streak', $stats['streak'])
            : null;
        $battle = self::pickTier('battle', $stats['battle_wins']);
        $topper = $stats['xp_percentile'] >= 50
            ? self::pickTier('topper', $stats['xp_percentile'])
            : null;

        $unlockedAchievements = self::resolveAchievementBadges($stats, $achievementKeys);
        $unlockedSpecial = self::resolveSpecialBadges($stats);

        $unlocked = self::dedupe(array_filter(array_merge(
            [$league, $streak, $battle, $topper],
            $unlockedAchievements,
            $unlockedSpecial
        )));

        return [
            'success' => true,
            'stats' => $stats,
            'primary' => [
                'league' => $league,
                'streak' => $streak,
                'battle' => $battle,
                'topper' => $topper,
            ],
            'progress' => [
                'streak' => self::tierProgress('streak', $stats['streak']),
                'league' => self::tierProgress('league', max(1, $stats['level'])),
            ],
            'unlocked' => $unlocked,
            'achievements' => $unlockedAchievements,
            'special' => $unlockedSpecial,
        ];
    }

    public static function collectStats(int $userId): array
    {
        $user = User::find($userId);
        if (!$user) {
            return self::emptyStats();
        }

        $analytics = LearningAnalyticsService::getOrCreateAnalytics($userId);
        $peer = LearningAnalyticsService::getPeerComparison($userId);

        $battleWins = StudyBattleParticipant::where('user_id', $userId)
            ->where('rank', 1)
            ->whereHas('room', fn ($q) => $q->where('status', 'completed'))
            ->count();

        $totalQuizzes = QuizAttempt::where('user_id', $userId)->count();

        $dailyCompleted = DailyChallengeAttempt::where('user_id', $userId)
            ->where('completed', true)
            ->whereDate('created_at', today())
            ->exists();

        $accuracy = round($analytics->overall_accuracy ?? 0, 1);

        return [
            'streak' => (int) ($user->current_streak ?? 0),
            'level' => (int) ($user->current_level ?? 1),
            'total_xp' => (int) ($user->total_xp ?? 0),
            'accuracy' => $accuracy,
            'questions_answered' => (int) ($analytics->total_questions ?? 0),
            'battle_wins' => $battleWins,
            'total_quizzes' => $totalQuizzes,
            'xp_percentile' => (int) ($peer['xp_percentile'] ?? 0),
            'daily_challenge_completed_today' => $dailyCompleted,
        ];
    }

    /**
     * Sync badge-tier achievements into user_achievements when thresholds are met.
     */
    public static function syncBadgeAchievements(int $userId): array
    {
        $stats = self::collectStats($userId);
        $awarded = [];

        $streakTiers = [
            3 => ['streak_3', '3-Day Streak', '🔥'],
            7 => ['streak_7', '7-Day Streak', '🔥'],
            15 => ['streak_15', '15-Day Streak', '⚡'],
            30 => ['streak_30', '30-Day Streak', '🏆'],
            60 => ['streak_60', '60-Day Streak', '💪'],
            100 => ['streak_100', '100-Day Streak', '👑'],
            365 => ['streak_365', '365-Day Streak', '🌟'],
        ];

        foreach ($streakTiers as $days => [$key, $name, $icon]) {
            if ($stats['streak'] >= $days) {
                $a = UserAchievement::award($userId, $key, $name, $icon, 'streaks');
                if ($a) {
                    $awarded[] = $a;
                }
            }
        }

        $levelTiers = [
            3 => ['league_bronze', 'Bronze League', '🥉'],
            6 => ['league_silver', 'Silver League', '🥈'],
            10 => ['league_gold', 'Gold League', '🥇'],
            15 => ['league_platinum', 'Platinum League', '💎'],
            20 => ['league_diamond', 'Diamond League', '💠'],
            30 => ['league_master', 'Master League', '👑'],
            40 => ['league_grandmaster', 'Grandmaster League', '🏅'],
            50 => ['league_legend', 'Legend League', '🌟'],
        ];

        foreach ($levelTiers as $minLevel => [$key, $name, $icon]) {
            if ($stats['level'] >= $minLevel) {
                $a = UserAchievement::award($userId, $key, $name, $icon, 'levels');
                if ($a) {
                    $awarded[] = $a;
                }
            }
        }

        if ($stats['total_quizzes'] >= 1) {
            $a = UserAchievement::award($userId, 'first_quiz', 'First Quiz', '🎯', 'questions');
            if ($a) {
                $awarded[] = $a;
            }
        }

        if ($stats['accuracy'] >= 90 && $stats['questions_answered'] >= 20) {
            $a = UserAchievement::award($userId, 'accuracy_king', 'Accuracy King', '🎯', 'accuracy');
            if ($a) {
                $awarded[] = $a;
            }
        }

        if ($stats['daily_challenge_completed_today']) {
            $a = UserAchievement::award($userId, 'daily_winner', 'Daily Winner', '🏅', 'challenges');
            if ($a) {
                $awarded[] = $a;
            }
        }

        if ($stats['battle_wins'] >= 5) {
            $a = UserAchievement::award($userId, 'battle_champion', 'Battle Champion', '⚔️', 'battles');
            if ($a) {
                $awarded[] = $a;
            }
        }

        return $awarded;
    }

    private static function emptyStats(): array
    {
        return [
            'streak' => 0,
            'level' => 1,
            'total_xp' => 0,
            'accuracy' => 0,
            'questions_answered' => 0,
            'battle_wins' => 0,
            'total_quizzes' => 0,
            'xp_percentile' => 0,
            'daily_challenge_completed_today' => false,
        ];
    }

    private static function pickTier(string $category, int $value, bool $defaultFirst = false): ?array
    {
        $tiers = config("badges.{$category}", []);
        if (empty($tiers)) {
            return null;
        }

        if ($value <= 0 && $defaultFirst && $category === 'streak') {
            return self::formatBadge($tiers[0], $category);
        }

        $selected = $tiers[0];
        foreach ($tiers as $tier) {
            if ($value >= ($tier['min'] ?? 0)) {
                $selected = $tier;
            }
        }

        return self::formatBadge($selected, $category);
    }

    private static function nextTier(string $category, int $value): ?array
    {
        $tiers = config("badges.{$category}", []);
        foreach ($tiers as $tier) {
            if ($value < ($tier['min'] ?? 0)) {
                return self::formatBadge($tier, $category);
            }
        }

        return null;
    }

    private static function tierProgress(string $category, int $value): array
    {
        $current = self::pickTier($category, $value, defaultFirst: $category === 'streak');
        $next = self::nextTier($category, $value);

        $progress = 1.0;
        $toNext = 0;

        if ($next && $current) {
            $start = $current['min_value'] ?? 0;
            $end = $next['min_value'] ?? $start;
            if ($end > $start) {
                $progress = min(1.0, max(0.0, ($value - $start) / ($end - $start)));
                $toNext = max(0, $end - $value);
            }
        }

        return [
            'percent' => round($progress * 100, 1),
            'current_badge' => $current,
            'next_badge' => $next,
            'value_to_next' => $toNext,
        ];
    }

    private static function resolveAchievementBadges(array $stats, array $keys): array
    {
        $defs = config('badges.achievement', []);
        $byId = collect($defs)->keyBy('id');
        $unlocked = [];

        if ($stats['total_quizzes'] >= 1 || $stats['questions_answered'] >= 1) {
            $unlocked[] = $byId->get('first_quiz');
        }
        if ($stats['total_quizzes'] >= 10 || in_array('questions_50', $keys, true)) {
            $unlocked[] = $byId->get('quiz_master');
        }
        if ($stats['accuracy'] >= 90 || self::hasKeyPrefix($keys, 'accuracy_')) {
            $unlocked[] = $byId->get('accuracy_king');
        }
        if ($stats['streak'] >= 7 || self::hasKeyPrefix($keys, 'streak_')) {
            $unlocked[] = $byId->get('consistency_beast');
        }
        if ($stats['xp_percentile'] >= 90) {
            $unlocked[] = $byId->get('top_performer');
        }

        return array_values(array_filter(array_map(
            fn ($b) => $b ? self::formatBadge($b, 'achievement') : null,
            $unlocked
        )));
    }

    private static function resolveSpecialBadges(array $stats): array
    {
        $defs = config('badges.special', []);
        $byId = collect($defs)->keyBy('id');
        $unlocked = [];

        if ($stats['daily_challenge_completed_today']) {
            $unlocked[] = $byId->get('daily_winner');
        }
        if ($stats['battle_wins'] >= 5) {
            $unlocked[] = $byId->get('battle_champion_special');
        }
        if ($stats['accuracy'] >= 100 && $stats['questions_answered'] >= 10) {
            $unlocked[] = $byId->get('perfect_score');
        }

        return array_values(array_filter(array_map(
            fn ($b) => $b ? self::formatBadge($b, 'special') : null,
            $unlocked
        )));
    }

    private static function hasKeyPrefix(array $keys, string $prefix): bool
    {
        foreach ($keys as $key) {
            if (str_starts_with($key, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private static function formatBadge(array $tier, string $category): array
    {
        return [
            'id' => $tier['id'],
            'name' => $tier['name'],
            'tagline' => $tier['tagline'],
            'category' => $category,
            'icon' => $tier['icon'],
            'colors' => [
                'gradient' => $tier['gradient'],
                'border' => $tier['border'],
            ],
            'min_value' => $tier['min'] ?? null,
            'max_value' => $tier['max'] ?? null,
        ];
    }

    private static function dedupe(array $badges): array
    {
        $seen = [];
        $out = [];
        foreach ($badges as $badge) {
            if (!$badge || isset($seen[$badge['id']])) {
                continue;
            }
            $seen[$badge['id']] = true;
            $out[] = $badge;
        }

        return $out;
    }
}
