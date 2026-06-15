<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAchievement extends Model
{
    protected $table = 'user_achievements';

    protected $fillable = [
        'user_id',
        'achievement_key',
        'achievement_name',
        'achievement_icon',
        'category',
        'unlocked_at',
    ];

    protected $casts = [
        'unlocked_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if user has a specific achievement
     */
    public static function hasAchievement(int $userId, string $achievementKey): bool
    {
        return self::where('user_id', $userId)
            ->where('achievement_key', $achievementKey)
            ->exists();
    }

    /**
     * Award an achievement to user
     */
    public static function award(int $userId, string $key, string $name, string $icon, string $category = 'general'): ?self
    {
        if (self::hasAchievement($userId, $key)) {
            return null; // Already has this achievement
        }

        return self::create([
            'user_id' => $userId,
            'achievement_key' => $key,
            'achievement_name' => $name,
            'achievement_icon' => $icon,
            'category' => $category,
            'unlocked_at' => now(),
        ]);
    }

    /**
     * Get all achievements for a user grouped by category
     */
    public static function getByCategory(int $userId): array
    {
        return self::where('user_id', $userId)
            ->orderBy('unlocked_at', 'desc')
            ->get()
            ->groupBy('category')
            ->toArray();
    }

    /**
     * Get recent achievements for user
     */
    public static function getRecent(int $userId, int $limit = 5): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('user_id', $userId)
            ->orderBy('unlocked_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Define all available achievements
     */
    public static function getAvailableAchievements(): array
    {
        return [
            // Streak achievements
            'streak_3' => ['name' => '3-Day Streak', 'icon' => '🔥', 'category' => 'streaks'],
            'streak_7' => ['name' => 'Week Warrior', 'icon' => '⚡', 'category' => 'streaks'],
            'streak_14' => ['name' => 'Fortnight Focus', 'icon' => '💪', 'category' => 'streaks'],
            'streak_30' => ['name' => 'Monthly Master', 'icon' => '🏆', 'category' => 'streaks'],
            'streak_100' => ['name' => 'Century Champion', 'icon' => '👑', 'category' => 'streaks'],

            // Question achievements
            'questions_10' => ['name' => 'First Steps', 'icon' => '🎯', 'category' => 'questions'],
            'questions_50' => ['name' => 'Curious Mind', 'icon' => '🧠', 'category' => 'questions'],
            'questions_100' => ['name' => 'Knowledge Seeker', 'icon' => '📚', 'category' => 'questions'],
            'questions_500' => ['name' => 'Quiz Master', 'icon' => '🎓', 'category' => 'questions'],
            'questions_1000' => ['name' => 'Scholar Supreme', 'icon' => '🌟', 'category' => 'questions'],

            // Accuracy achievements
            'accuracy_80' => ['name' => 'Sharp Shooter', 'icon' => '🎯', 'category' => 'accuracy'],
            'accuracy_90' => ['name' => 'Precision Pro', 'icon' => '💎', 'category' => 'accuracy'],
            'accuracy_95' => ['name' => 'Near Perfect', 'icon' => '⭐', 'category' => 'accuracy'],
            'perfect_10' => ['name' => '10 in a Row', 'icon' => '🔟', 'category' => 'accuracy'],

            // Daily Challenge achievements
            'daily_first' => ['name' => 'Challenge Accepted', 'icon' => '🏅', 'category' => 'challenges'],
            'daily_week' => ['name' => 'Weekly Warrior', 'icon' => '🗓️', 'category' => 'challenges'],
            'daily_month' => ['name' => 'Monthly Champion', 'icon' => '🏆', 'category' => 'challenges'],

            // Time-based achievements
            'night_owl' => ['name' => 'Night Owl', 'icon' => '🦉', 'category' => 'time'],
            'early_bird' => ['name' => 'Early Bird', 'icon' => '🐦', 'category' => 'time'],
            'weekend_warrior' => ['name' => 'Weekend Warrior', 'icon' => '🎉', 'category' => 'time'],

            // Level achievements
            'level_5' => ['name' => 'Rising Star', 'icon' => '⭐', 'category' => 'levels'],
            'level_10' => ['name' => 'Dedicated Learner', 'icon' => '🌟', 'category' => 'levels'],
            'level_25' => ['name' => 'Expert Student', 'icon' => '💫', 'category' => 'levels'],
            'level_50' => ['name' => 'Learning Legend', 'icon' => '👑', 'category' => 'levels'],

            // Subject mastery
            'topic_master' => ['name' => 'Topic Master', 'icon' => '🏅', 'category' => 'mastery'],
            'subject_expert' => ['name' => 'Subject Expert', 'icon' => '🎖️', 'category' => 'mastery'],
            'multi_talented' => ['name' => 'Multi-Talented', 'icon' => '🌈', 'category' => 'mastery'],
        ];
    }
}
