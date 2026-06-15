<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserLearningAnalytics;
use App\Models\TopicPerformance;
use App\Models\StudySession;
use App\Models\UserAchievement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class LearningAnalyticsService
{
    // XP required for each level (increases progressively)
    private const LEVEL_XP_REQUIREMENTS = [
        1 => 0,
        2 => 100,
        3 => 250,
        4 => 450,
        5 => 700,
        6 => 1000,
        7 => 1400,
        8 => 1900,
        9 => 2500,
        10 => 3200,
        11 => 4000,
        12 => 5000,
        13 => 6200,
        14 => 7600,
        15 => 9200,
        16 => 11000,
        17 => 13000,
        18 => 15500,
        19 => 18500,
        20 => 22000,
        25 => 35000,
        30 => 52000,
        40 => 95000,
        50 => 150000,
    ];

    /**
     * Get or create learning analytics record for user
     */
    public static function getOrCreateAnalytics(int $userId): UserLearningAnalytics
    {
        return UserLearningAnalytics::firstOrCreate(
            ['user_id' => $userId],
            [
                'learning_style' => null,
                'diagram_requests' => 0,
                'example_requests' => 0,
                'practical_questions' => 0,
                'theory_questions' => 0,
                'total_questions' => 0,
                'correct_answers' => 0,
                'overall_accuracy' => 0,
                'last_10_correct' => 0,
                'last_10_accuracy' => 0,
                'preferred_language' => 'english',
                'hindi_messages' => 0,
                'hinglish_messages' => 0,
                'english_messages' => 0,
            ]
        );
    }

    // ==========================================
    // LEARNING STYLE DETECTION & ADAPTATION
    // ==========================================

    /**
     * Analyze message content to detect learning style preferences
     */
    public static function analyzeMessageForLearningStyle(int $userId, string $message): void
    {
        $analytics = self::getOrCreateAnalytics($userId);
        $messageLower = strtolower($message);

        // Visual learner indicators
        $visualKeywords = ['diagram', 'image', 'picture', 'visual', 'show me', 'draw', 'chart', 'graph', 'flowchart', 'figure'];
        foreach ($visualKeywords as $keyword) {
            if (str_contains($messageLower, $keyword)) {
                $analytics->diagram_requests++;
                break;
            }
        }

        // Verbal learner indicators
        $verbalKeywords = ['example', 'explain', 'describe', 'tell me', 'what is', 'define', 'elaborate', 'story', 'analogy'];
        foreach ($verbalKeywords as $keyword) {
            if (str_contains($messageLower, $keyword)) {
                $analytics->example_requests++;
                break;
            }
        }

        // Practical learner indicators
        $practicalKeywords = ['solve', 'calculate', 'problem', 'practice', 'exercise', 'quiz', 'test', 'apply', 'how to'];
        foreach ($practicalKeywords as $keyword) {
            if (str_contains($messageLower, $keyword)) {
                $analytics->practical_questions++;
                break;
            }
        }

        // Theoretical learner indicators
        $theoryKeywords = ['why', 'concept', 'theory', 'principle', 'understand', 'logic', 'reason', 'derivation', 'proof'];
        foreach ($theoryKeywords as $keyword) {
            if (str_contains($messageLower, $keyword)) {
                $analytics->theory_questions++;
                break;
            }
        }

        // Detect and update learning style
        $analytics->learning_style = $analytics->detectLearningStyle();
        $analytics->save();
    }

    /**
     * Get personalized prompt additions based on learning style
     */
    public static function getLearningStylePrompt(int $userId): string
    {
        $analytics = self::getOrCreateAnalytics($userId);
        $style = $analytics->learning_style ?? 'verbal';

        $prompts = [
            'visual' => "This student is a VISUAL learner. Always include:
- Diagrams, flowcharts, or ASCII art when explaining concepts
- Use tables to organize information
- Describe visual representations (\"Imagine a diagram where...\")
- Use spatial language and visual metaphors
- Format with clear sections and bullet points",

            'verbal' => "This student is a VERBAL learner. Always:
- Use clear, detailed explanations with examples
- Include analogies and real-world comparisons
- Tell stories to illustrate concepts
- Define technical terms in simple language
- Use step-by-step explanations",

            'practical' => "This student is a PRACTICAL learner. Always:
- Start with practical applications and examples
- Include practice problems immediately after concepts
- Show worked solutions step-by-step
- Give tips for solving similar problems
- Focus on \"how to\" rather than \"why\"",

            'theoretical' => "This student is a THEORETICAL learner. Always:
- Explain the underlying principles and logic first
- Include derivations and proofs when relevant
- Connect concepts to broader theories
- Explain \"why\" before \"how\"
- Discuss edge cases and exceptions",
        ];

        return $prompts[$style] ?? $prompts['verbal'];
    }

    // ==========================================
    // LANGUAGE DETECTION & MULTI-LANGUAGE SUPPORT
    // ==========================================

    /**
     * Detect message language and update preferences
     */
    public static function detectAndTrackLanguage(int $userId, string $message): string
    {
        $analytics = self::getOrCreateAnalytics($userId);

        // Hindi script detection (Devanagari)
        if (preg_match('/[\x{0900}-\x{097F}]/u', $message)) {
            $analytics->hindi_messages++;
            $analytics->preferred_language = $analytics->detectPreferredLanguage();
            $analytics->save();
            return 'hindi';
        }

        // Hinglish detection (common Hindi words in English script)
        $hinglishWords = ['kya', 'hai', 'kaise', 'karo', 'samjhao', 'bata', 'nahi', 'haan', 'accha', 'theek', 'kar', 'de', 'le', 'mein', 'yeh', 'woh', 'aur', 'bhi', 'toh', 'na'];
        $messageLower = strtolower($message);
        $hinglishCount = 0;
        foreach ($hinglishWords as $word) {
            if (preg_match('/\b' . $word . '\b/', $messageLower)) {
                $hinglishCount++;
            }
        }

        if ($hinglishCount >= 2) {
            $analytics->hinglish_messages++;
            $analytics->preferred_language = $analytics->detectPreferredLanguage();
            $analytics->save();
            return 'hinglish';
        }

        // Default to English
        $analytics->english_messages++;
        $analytics->preferred_language = $analytics->detectPreferredLanguage();
        $analytics->save();
        return 'english';
    }

    /**
     * Get language-based prompt addition
     */
    public static function getLanguagePrompt(int $userId): string
    {
        $analytics = self::getOrCreateAnalytics($userId);
        $language = $analytics->preferred_language ?? 'english';

        return match ($language) {
            'hindi' => "This student prefers Hindi. Respond primarily in Hindi (Devanagari script). Use simple Hindi with occasional English technical terms when needed.",
            'hinglish' => "This student prefers Hinglish (Hindi-English mix). Respond in a friendly Hinglish style - mix Hindi and English naturally. Use Roman script for Hindi words. Example: 'Yeh concept simple hai, let me explain...'",
            default => "Respond in clear, simple English suitable for Indian students.",
        };
    }

    // ==========================================
    // MOOD DETECTION & EMPATHETIC RESPONSES
    // ==========================================

    /**
     * Detect user's mood from message
     */
    public static function detectMood(string $message): string
    {
        $messageLower = strtolower($message);

        // Frustrated indicators
        $frustratedWords = ['confused', 'don\'t understand', 'nahi samajh', 'samajh nahi', 'difficult', 'hard', 'frustrated', 'hate', 'boring', 'stupid', 'can\'t', 'impossible', 'ugh', 'argh'];
        foreach ($frustratedWords as $word) {
            if (str_contains($messageLower, $word)) {
                return 'frustrated';
            }
        }

        // Anxious indicators
        $anxiousWords = ['exam', 'test', 'nervous', 'worried', 'scared', 'fail', 'tension', 'stress', 'panic', 'help me', 'urgent', 'tomorrow'];
        $anxiousCount = 0;
        foreach ($anxiousWords as $word) {
            if (str_contains($messageLower, $word)) {
                $anxiousCount++;
            }
        }
        if ($anxiousCount >= 2) {
            return 'anxious';
        }

        // Excited/Happy indicators
        $excitedWords = ['got it', 'understood', 'amazing', 'great', 'thanks', 'thank you', 'awesome', 'perfect', 'love', 'interesting', 'cool', 'wow'];
        foreach ($excitedWords as $word) {
            if (str_contains($messageLower, $word)) {
                return 'excited';
            }
        }

        // Curious indicators
        $curiousWords = ['why', 'how', 'what if', 'curious', 'wonder', 'interesting'];
        foreach ($curiousWords as $word) {
            if (str_contains($messageLower, $word)) {
                return 'curious';
            }
        }

        return 'neutral';
    }

    /**
     * Get mood-based prompt addition
     */
    public static function getMoodPrompt(string $mood): string
    {
        return match ($mood) {
            'frustrated' => "The student seems frustrated. Be extra patient and encouraging. Break down concepts into smaller steps. Acknowledge their difficulty and reassure them. Use phrases like 'I understand this can be tricky, let's take it step by step...'",
            'anxious' => "The student seems anxious about exams. Be calm and reassuring. Focus on building confidence. Give practical tips. Use phrases like 'Don't worry, you've got this! Let's focus on...'",
            'excited' => "The student is engaged and excited! Build on this enthusiasm. Offer to explore deeper or try harder questions. Be enthusiastic in your response.",
            'curious' => "The student is curious and wants to learn more. Provide detailed explanations. Suggest related topics they might find interesting. Encourage their curiosity.",
            default => "",
        };
    }

    // ==========================================
    // PERFORMANCE TRACKING & DIFFICULTY ADJUSTMENT
    // ==========================================

    /**
     * Track question performance
     */
    public static function trackPerformance(int $userId, string $topic, string $subject, bool $correct, int $timeTaken = 0): void
    {
        // Update topic performance
        $performance = TopicPerformance::firstOrCreate(
            ['user_id' => $userId, 'topic' => $topic, 'subject' => $subject],
            [
                'attempts' => 0,
                'correct_answers' => 0,
                'total_time_spent' => 0,
                'difficulty_level' => 'medium',
                'success_rate' => 0,
                'first_learned_at' => now(),
            ]
        );

        $performance->attempts++;
        if ($correct) {
            $performance->correct_answers++;
        }
        $performance->total_time_spent += $timeTaken;
        $performance->last_attempt = now();
        $performance->updateSuccessRate();
        $performance->needs_revision = $performance->checkRevisionNeeded();
        $performance->save();

        // Update overall analytics
        $analytics = self::getOrCreateAnalytics($userId);
        $analytics->total_questions++;
        if ($correct) {
            $analytics->correct_answers++;
        }
        $analytics->overall_accuracy = ($analytics->correct_answers / $analytics->total_questions) * 100;
        $analytics->save();

        // Update user model accuracy
        $user = User::find($userId);
        if ($user) {
            $user->questions_answered = ($user->questions_answered ?? 0) + 1;
            $user->overall_accuracy = $analytics->overall_accuracy;
            $user->save();
        }

        // Check for achievements
        self::checkAndAwardAchievements($userId);
    }

    /**
     * Get performance-based prompt addition
     */
    public static function getPerformancePrompt(int $userId): string
    {
        $analytics = self::getOrCreateAnalytics($userId);
        $tier = $analytics->getPerformanceTier();

        return match ($tier) {
            'struggling' => "This student has low accuracy ({$analytics->overall_accuracy}%). Be extra supportive. Use simpler language. Break concepts into smaller parts. Provide more examples. Focus on building confidence.",
            'high_performer' => "This student has high accuracy ({$analytics->overall_accuracy}%). Challenge them with deeper concepts. Offer advanced variations. Encourage them to try harder problems.",
            default => "Maintain a balanced approach with this student.",
        };
    }

    /**
     * Get weak topics for spaced repetition
     */
    public static function getWeakTopics(int $userId, int $limit = 5): array
    {
        return TopicPerformance::weakTopics($userId)
            ->limit($limit)
            ->get()
            ->map(fn($t) => [
                'topic' => $t->topic,
                'subject' => $t->subject,
                'success_rate' => $t->success_rate,
                'attempts' => $t->attempts,
            ])
            ->toArray();
    }

    /**
     * Get topics needing revision
     */
    public static function getRevisionTopics(int $userId): array
    {
        return TopicPerformance::needsRevision($userId)
            ->limit(5)
            ->get()
            ->map(fn($t) => [
                'topic' => $t->topic,
                'subject' => $t->subject,
                'last_attempt' => $t->last_attempt?->diffForHumans(),
                'success_rate' => $t->success_rate,
            ])
            ->toArray();
    }

    /**
     * Get revision reminder prompt
     */
    public static function getRevisionReminder(int $userId): string
    {
        $revisionTopics = self::getRevisionTopics($userId);

        if (empty($revisionTopics)) {
            return "";
        }

        $topicList = collect($revisionTopics)->pluck('topic')->take(3)->implode(', ');
        return "REVISION REMINDER: Suggest the student practice these topics they haven't visited recently: {$topicList}. You can mention this naturally in your response.";
    }

    // ==========================================
    // GAMIFICATION (XP, LEVELS, ACHIEVEMENTS)
    // ==========================================

    /**
     * Award XP to user
     */
    public static function awardXP(int $userId, int $xp, string $reason = ''): array
    {
        $user = User::find($userId);
        if (!$user) {
            return ['success' => false];
        }

        $oldLevel = $user->current_level ?? 1;
        $user->total_xp = ($user->total_xp ?? 0) + $xp;

        // Calculate new level
        $newLevel = self::calculateLevel($user->total_xp);
        $user->current_level = $newLevel;
        $user->save();

        $leveledUp = $newLevel > $oldLevel;

        // Check level achievements
        if ($leveledUp) {
            self::checkLevelAchievements($userId, $newLevel);
        }

        return [
            'success' => true,
            'xp_awarded' => $xp,
            'total_xp' => $user->total_xp,
            'old_level' => $oldLevel,
            'new_level' => $newLevel,
            'leveled_up' => $leveledUp,
            'reason' => $reason,
        ];
    }

    /**
     * Calculate level from XP
     */
    public static function calculateLevel(int $xp): int
    {
        $level = 1;
        foreach (self::LEVEL_XP_REQUIREMENTS as $lvl => $required) {
            if ($xp >= $required) {
                $level = $lvl;
            } else {
                break;
            }
        }
        return $level;
    }

    /**
     * Get XP required for next level
     */
    public static function getXPForNextLevel(int $currentXP): array
    {
        $currentLevel = self::calculateLevel($currentXP);
        $nextLevel = $currentLevel + 1;

        // Find next level XP requirement
        $nextXP = 0;
        foreach (self::LEVEL_XP_REQUIREMENTS as $lvl => $required) {
            if ($lvl > $currentLevel) {
                $nextXP = $required;
                break;
            }
        }

        // If we're past defined levels, calculate progressively
        if ($nextXP === 0) {
            $lastDefined = array_key_last(self::LEVEL_XP_REQUIREMENTS);
            $lastXP = self::LEVEL_XP_REQUIREMENTS[$lastDefined];
            $nextXP = $lastXP + (($currentLevel - $lastDefined + 1) * 5000);
        }

        $currentLevelXP = self::LEVEL_XP_REQUIREMENTS[$currentLevel] ?? 0;
        $progress = $nextXP > $currentLevelXP
            ? (($currentXP - $currentLevelXP) / ($nextXP - $currentLevelXP)) * 100
            : 100;

        return [
            'current_level' => $currentLevel,
            'current_xp' => $currentXP,
            'next_level' => $nextLevel,
            'xp_for_next' => $nextXP,
            'xp_needed' => max(0, $nextXP - $currentXP),
            'progress_percent' => round($progress, 1),
        ];
    }

    /**
     * Get gamification prompt for AI
     */
    public static function getGamificationPrompt(int $userId): string
    {
        $user = User::find($userId);
        if (!$user) {
            return "";
        }

        $xpInfo = self::getXPForNextLevel($user->total_xp ?? 0);
        $recentAchievements = UserAchievement::getRecent($userId, 3);

        $prompt = "GAMIFICATION CONTEXT: Student is Level {$xpInfo['current_level']} with {$xpInfo['current_xp']} XP. ";
        $prompt .= "They need {$xpInfo['xp_needed']} more XP to reach Level {$xpInfo['next_level']}. ";

        if ($recentAchievements->isNotEmpty()) {
            $achievements = $recentAchievements->pluck('achievement_name')->implode(', ');
            $prompt .= "Recent achievements: {$achievements}. ";
        }

        $prompt .= "Occasionally acknowledge their progress or encourage them to keep going!";

        return $prompt;
    }

    /**
     * Check and award achievements
     */
    public static function checkAndAwardAchievements(int $userId): array
    {
        $awarded = [];
        $user = User::find($userId);
        $analytics = self::getOrCreateAnalytics($userId);

        // Streak achievements
        $streak = $user->current_streak ?? 0;
        if ($streak >= 3) {
            $a = UserAchievement::award($userId, 'streak_3', '3-Day Streak', '🔥', 'streaks');
            if ($a) $awarded[] = $a;
        }
        if ($streak >= 7) {
            $a = UserAchievement::award($userId, 'streak_7', 'Week Warrior', '⚡', 'streaks');
            if ($a) $awarded[] = $a;
        }
        if ($streak >= 14) {
            $a = UserAchievement::award($userId, 'streak_14', 'Fortnight Focus', '💪', 'streaks');
            if ($a) $awarded[] = $a;
        }
        if ($streak >= 30) {
            $a = UserAchievement::award($userId, 'streak_30', 'Monthly Master', '🏆', 'streaks');
            if ($a) $awarded[] = $a;
        }

        // Question achievements
        $questions = $analytics->total_questions ?? 0;
        if ($questions >= 10) {
            $a = UserAchievement::award($userId, 'questions_10', 'First Steps', '🎯', 'questions');
            if ($a) $awarded[] = $a;
        }
        if ($questions >= 50) {
            $a = UserAchievement::award($userId, 'questions_50', 'Curious Mind', '🧠', 'questions');
            if ($a) $awarded[] = $a;
        }
        if ($questions >= 100) {
            $a = UserAchievement::award($userId, 'questions_100', 'Knowledge Seeker', '📚', 'questions');
            if ($a) $awarded[] = $a;
        }
        if ($questions >= 500) {
            $a = UserAchievement::award($userId, 'questions_500', 'Quiz Master', '🎓', 'questions');
            if ($a) $awarded[] = $a;
        }

        // Accuracy achievements
        $accuracy = $analytics->overall_accuracy ?? 0;
        if ($accuracy >= 80 && $questions >= 20) {
            $a = UserAchievement::award($userId, 'accuracy_80', 'Sharp Shooter', '🎯', 'accuracy');
            if ($a) $awarded[] = $a;
        }
        if ($accuracy >= 90 && $questions >= 30) {
            $a = UserAchievement::award($userId, 'accuracy_90', 'Precision Pro', '💎', 'accuracy');
            if ($a) $awarded[] = $a;
        }

        // Time-based achievements
        $hour = now()->hour;
        if ($hour >= 22 || $hour < 5) {
            $a = UserAchievement::award($userId, 'night_owl', 'Night Owl', '🦉', 'time');
            if ($a) $awarded[] = $a;
        }
        if ($hour >= 5 && $hour < 7) {
            $a = UserAchievement::award($userId, 'early_bird', 'Early Bird', '🐦', 'time');
            if ($a) $awarded[] = $a;
        }

        return $awarded;
    }

    /**
     * Check level-based achievements
     */
    private static function checkLevelAchievements(int $userId, int $level): void
    {
        if ($level >= 5) {
            UserAchievement::award($userId, 'level_5', 'Rising Star', '⭐', 'levels');
        }
        if ($level >= 10) {
            UserAchievement::award($userId, 'level_10', 'Dedicated Learner', '🌟', 'levels');
        }
        if ($level >= 25) {
            UserAchievement::award($userId, 'level_25', 'Expert Student', '💫', 'levels');
        }
        if ($level >= 50) {
            UserAchievement::award($userId, 'level_50', 'Learning Legend', '👑', 'levels');
        }
    }

    // ==========================================
    // PEER COMPARISON
    // ==========================================

    /**
     * Get peer comparison stats
     */
    public static function getPeerComparison(int $userId): array
    {
        $user = User::find($userId);
        if (!$user) {
            return [];
        }

        $analytics = self::getOrCreateAnalytics($userId);

        // Get percentile rankings (cached for 1 hour)
        $cacheKey = "peer_comparison_{$userId}";
        return Cache::remember($cacheKey, 3600, function () use ($user, $analytics) {
            $totalUsers = User::count();

            // XP ranking
            $xpRank = User::where('total_xp', '>', $user->total_xp ?? 0)->count() + 1;
            $xpPercentile = round((($totalUsers - $xpRank) / max(1, $totalUsers)) * 100);

            // Streak ranking
            $streakRank = User::where('current_streak', '>', $user->current_streak ?? 0)->count() + 1;
            $streakPercentile = round((($totalUsers - $streakRank) / max(1, $totalUsers)) * 100);

            // Accuracy ranking (only for users with 10+ questions)
            $accuracyRank = UserLearningAnalytics::where('total_questions', '>=', 10)
                ->where('overall_accuracy', '>', $analytics->overall_accuracy ?? 0)
                ->count() + 1;
            $usersWithAccuracy = UserLearningAnalytics::where('total_questions', '>=', 10)->count();
            $accuracyPercentile = $usersWithAccuracy > 0
                ? round((($usersWithAccuracy - $accuracyRank) / $usersWithAccuracy) * 100)
                : 50;

            return [
                'xp_rank' => $xpRank,
                'xp_percentile' => $xpPercentile,
                'streak_rank' => $streakRank,
                'streak_percentile' => $streakPercentile,
                'accuracy_rank' => $accuracyRank,
                'accuracy_percentile' => $accuracyPercentile,
                'total_users' => $totalUsers,
            ];
        });
    }

    /**
     * Get peer comparison prompt
     */
    public static function getPeerComparisonPrompt(int $userId): string
    {
        $comparison = self::getPeerComparison($userId);

        if (empty($comparison)) {
            return "";
        }

        $prompt = "PEER CONTEXT: ";

        if ($comparison['xp_percentile'] >= 80) {
            $prompt .= "This student is in the top {$comparison['xp_percentile']}% for XP - they're doing great! ";
        }

        if ($comparison['streak_percentile'] >= 70) {
            $prompt .= "They have one of the better streaks (top {$comparison['streak_percentile']}%). ";
        }

        if ($comparison['accuracy_percentile'] >= 80) {
            $prompt .= "Their accuracy is excellent (top {$comparison['accuracy_percentile']}%). Challenge them more! ";
        } elseif ($comparison['accuracy_percentile'] < 30) {
            $prompt .= "Their accuracy needs improvement. Be patient and supportive. ";
        }

        return $prompt;
    }

    // ==========================================
    // SESSION ANALYTICS
    // ==========================================

    /**
     * Start or continue a study session
     */
    public static function startOrContinueSession(int $userId): StudySession
    {
        // Check for active session (started in last 30 minutes)
        $activeSession = StudySession::where('user_id', $userId)
            ->whereNull('end_time')
            ->where('start_time', '>=', now()->subMinutes(30))
            ->first();

        if ($activeSession) {
            return $activeSession;
        }

        // End any old sessions
        StudySession::where('user_id', $userId)
            ->whereNull('end_time')
            ->update(['end_time' => now()]);

        // Start new session
        return StudySession::startSession($userId);
    }

    /**
     * Record activity in current session
     */
    public static function recordSessionActivity(int $userId, string $topic = null): void
    {
        $session = self::startOrContinueSession($userId);
        $session->addQuestion($topic);
    }

    /**
     * End current session and calculate rewards
     */
    public static function endSession(int $userId): array
    {
        $session = StudySession::getActiveSession($userId);

        if (!$session) {
            return ['success' => false, 'message' => 'No active session'];
        }

        $session->endSession();
        $xpEarned = $session->calculateXP();
        $session->save();

        // Award XP
        $xpResult = self::awardXP($userId, $xpEarned, 'study_session');

        return [
            'success' => true,
            'session_duration' => $session->duration,
            'questions_asked' => $session->questions_asked,
            'topics_covered' => $session->topics_covered,
            'focus_score' => $session->focus_score,
            'productivity' => $session->productivity_rating,
            'xp_earned' => $xpEarned,
            'leveled_up' => $xpResult['leveled_up'] ?? false,
            'new_level' => $xpResult['new_level'] ?? null,
        ];
    }

    /**
     * Get session stats for today
     */
    public static function getTodayStats(int $userId): array
    {
        $sessions = StudySession::today($userId)->get();

        return [
            'total_sessions' => $sessions->count(),
            'total_duration' => $sessions->sum('duration'),
            'total_questions' => $sessions->sum('questions_asked'),
            'total_xp' => $sessions->sum('xp_earned'),
            'avg_focus_score' => round($sessions->avg('focus_score') ?? 0, 1),
        ];
    }

    // ==========================================
    // EXAM STRATEGY MODE
    // ==========================================

    /**
     * Get exam strategy prompt based on days remaining
     */
    public static function getExamStrategyPrompt(int $userId): string
    {
        $user = User::find($userId);
        if (!$user || !$user->exam_date) {
            return "";
        }

        $daysRemaining = now()->diffInDays($user->exam_date, false);

        if ($daysRemaining < 0) {
            return ""; // Exam passed
        }

        if ($daysRemaining <= 3) {
            return "EXAM MODE (URGENT - {$daysRemaining} days left): Focus on quick revision, formulas, and key points only. No new topics. Boost confidence. Provide summary sheets and last-minute tips.";
        }

        if ($daysRemaining <= 7) {
            return "EXAM MODE (CRITICAL - {$daysRemaining} days left): Focus on important topics and previous year questions. Provide solved examples. Quick revision notes. Practice time management.";
        }

        if ($daysRemaining <= 14) {
            return "EXAM MODE ({$daysRemaining} days left): Balance between revision and practice. Focus on weak areas. Provide mock test questions. Help with doubt clearing.";
        }

        if ($daysRemaining <= 30) {
            return "EXAM PREP ({$daysRemaining} days left): Systematic preparation. Cover important topics thoroughly. Regular practice problems. Track progress.";
        }

        return ""; // More than 30 days, no special mode
    }

    // ==========================================
    // COMPREHENSIVE ANALYTICS PROMPT GENERATOR
    // ==========================================

    /**
     * Generate complete personalized prompt with all analytics
     */
    public static function generateComprehensivePrompt(int $userId, string $message): string
    {
        $prompts = [];

        // CRITICAL: Skip all extra prompts for greetings to prevent topic confusion
        // The AI was responding with "ELECTROMAGNETIC INDUCTION" to "Hii" because
        // revision reminders were being added with topics like electromagnetic induction
        $messageLower = strtolower(trim($message));
        $greetings = ['hi', 'hii', 'hiii', 'hello', 'hey', 'hola', 'namaste', 'namaskar', 'good morning', 'good afternoon', 'good evening', 'gm', 'gn'];
        $isGreeting = in_array($messageLower, $greetings) || strlen($messageLower) <= 5;

        if ($isGreeting) {
            // For greetings, return empty - let the base system prompt handle it
            // Don't add revision reminders, exam strategy, etc. that could confuse the AI
            return "";
        }

        // Learning style
        self::analyzeMessageForLearningStyle($userId, $message);
        $prompts[] = self::getLearningStylePrompt($userId);

        // Language
        self::detectAndTrackLanguage($userId, $message);
        $prompts[] = self::getLanguagePrompt($userId);

        // Mood
        $mood = self::detectMood($message);
        if ($moodPrompt = self::getMoodPrompt($mood)) {
            $prompts[] = $moodPrompt;
        }

        // Performance
        $prompts[] = self::getPerformancePrompt($userId);

        // FEEDBACK-BASED PERSONALIZATION (highest priority)
        try {
            $feedbackPrompt = FeedbackAnalysisService::generateFeedbackAwarePrompt($userId);
            if (!empty($feedbackPrompt)) {
                $prompts[] = $feedbackPrompt;
            }
        } catch (\Exception $e) {
            Log::warning("Failed to generate feedback prompt: " . $e->getMessage());
        }

        // Revision reminder (30% chance to avoid being repetitive)
        if (rand(1, 100) <= 30) {
            if ($revisionPrompt = self::getRevisionReminder($userId)) {
                $prompts[] = $revisionPrompt;
            }
        }

        // Gamification
        $prompts[] = self::getGamificationPrompt($userId);

        // Peer comparison (20% chance)
        if (rand(1, 100) <= 20) {
            if ($peerPrompt = self::getPeerComparisonPrompt($userId)) {
                $prompts[] = $peerPrompt;
            }
        }

        // Exam strategy
        if ($examPrompt = self::getExamStrategyPrompt($userId)) {
            $prompts[] = $examPrompt;
        }

        // Record session activity
        self::recordSessionActivity($userId);

        return implode("\n\n", array_filter($prompts));
    }

    // ==========================================
    // API DATA METHODS
    // ==========================================

    /**
     * Weak topics from recent low-scoring quiz attempts.
     */
    public static function getWeakTopicsFromQuizHistory(int $userId, int $limit = 5): array
    {
        $attempts = \App\Models\QuizAttempt::where('user_id', $userId)
            ->completed()
            ->whereNotNull('topic')
            ->orderByDesc('created_at')
            ->limit(30)
            ->get();

        $lowScore = $attempts->filter(fn ($a) => (float) $a->score < 65);
        $source = $lowScore->isNotEmpty() ? $lowScore : $attempts;

        return $source->unique('topic')->take($limit)->map(fn ($a) => [
            'topic' => $a->topic,
            'subject' => $a->subject ?? 'General',
            'success_rate' => (float) $a->score,
            'attempts' => 1,
            'source' => 'quiz_history',
        ])->values()->toArray();
    }

    /**
     * Merge performance-tracked weak topics with quiz history fallback.
     */
    public static function getMergedWeakTopics(int $userId, int $limit = 5): array
    {
        $performance = self::getWeakTopics($userId, $limit);
        if (!empty($performance)) {
            return array_map(
                fn ($t) => array_merge($t, ['source' => 'performance']),
                $performance
            );
        }

        $fromQuiz = self::getWeakTopicsFromQuizHistory($userId, $limit);
        if (!empty($fromQuiz)) {
            return $fromQuiz;
        }

        return self::getDefaultStarterTopics();
    }

    /**
     * Default topics for new users without performance data.
     */
    public static function getDefaultStarterTopics(): array
    {
        return [
            ['topic' => 'Laws of Motion', 'subject' => 'Physics', 'success_rate' => 0, 'attempts' => 0, 'source' => 'default'],
            ['topic' => 'Chemical Bonding', 'subject' => 'Chemistry', 'success_rate' => 0, 'attempts' => 0, 'source' => 'default'],
            ['topic' => 'Quadratic Equations', 'subject' => 'Maths', 'success_rate' => 0, 'attempts' => 0, 'source' => 'default'],
            ['topic' => 'Cell Structure', 'subject' => 'Biology', 'success_rate' => 0, 'attempts' => 0, 'source' => 'default'],
        ];
    }

    /**
     * Personalized weekly revision plan from weak + revision-needed topics.
     */
    public static function buildRevisionPlan(int $userId): array
    {
        $analytics = self::getOrCreateAnalytics($userId);
        $user = User::find($userId);

        $revisionTopics = collect(self::getRevisionTopics($userId));
        $weakTopics = collect(self::getMergedWeakTopics($userId, 6));

        $pool = $revisionTopics
            ->merge($weakTopics)
            ->unique(fn ($t) => ($t['subject'] ?? '') . '|' . ($t['topic'] ?? ''))
            ->values();

        if ($pool->isEmpty()) {
            $pool = collect(self::getDefaultStarterTopics());
        }

        $studyDays = $pool->take(4)->values();
        $days = [];
        $dayNum = 1;

        foreach ($studyDays as $topic) {
            $days[] = self::formatPlanDay($userId, $dayNum++, $topic, 'revise');
        }

        $defaults = self::getDefaultStarterTopics();
        while (count($days) < 4) {
            $topic = $defaults[count($days) % count($defaults)];
            $days[] = self::formatPlanDay($userId, $dayNum++, $topic, 'revise');
        }

        $days[] = self::formatPlanDay($userId, 5, [
            'topic' => 'Weekly Practice Test',
            'subject' => 'Mixed',
            'success_rate' => null,
        ], 'mock_test');

        return [
            'plan_id' => 'week_' . now()->format('Y_W'),
            'user_accuracy' => round($analytics->overall_accuracy ?? 0, 1),
            'user_streak' => $user->current_streak ?? 0,
            'weak_count' => $weakTopics->count(),
            'personalized' => $revisionTopics->isNotEmpty() || $weakTopics->where('source', '!=', 'default')->isNotEmpty(),
            'days' => $days,
        ];
    }

    private static function formatPlanDay(int $userId, int $dayNum, array $topic, string $action): array
    {
        return [
            'day' => $dayNum,
            'topic' => $topic['topic'],
            'subject' => $topic['subject'] ?? 'General',
            'action' => $action,
            'success_rate' => isset($topic['success_rate']) ? (float) $topic['success_rate'] : null,
            'completed' => self::isPlanDayCompleted($userId, $topic, $action),
        ];
    }

    private static function isPlanDayCompleted(int $userId, array $topic, string $action): bool
    {
        $weekStart = now()->startOfWeek();

        if ($action === 'mock_test') {
            return \App\Models\QuizAttempt::where('user_id', $userId)
                ->completed()
                ->where('created_at', '>=', $weekStart)
                ->where('score', '>=', 50)
                ->exists();
        }

        $topicName = $topic['topic'] ?? '';
        if (empty($topicName)) {
            return false;
        }

        return \App\Models\QuizAttempt::where('user_id', $userId)
            ->completed()
            ->where('topic', $topicName)
            ->where('created_at', '>=', $weekStart)
            ->where('score', '>=', 55)
            ->exists();
    }

    /**
     * Flashcards prioritizing weak topics + competitive exam formulas.
     */
    public static function getFlashcards(int $userId, ?string $subjectFilter = null): array
    {
        $weak = self::getMergedWeakTopics($userId, 8);
        $cards = [];

        foreach ($weak as $wt) {
            if ($subjectFilter && strcasecmp($wt['subject'], $subjectFilter) !== 0) {
                continue;
            }
            $rate = round($wt['success_rate'] ?? 0);
            $cards[] = [
                'front' => $wt['topic'],
                'back' => "Focus revision — {$wt['subject']} (accuracy {$rate}%)",
                'subject' => $wt['subject'],
                'type' => 'weak_topic',
            ];
        }

        foreach (self::getFormulaBank() as $formula) {
            if ($subjectFilter && strcasecmp($formula['subject'], $subjectFilter) !== 0) {
                continue;
            }
            $cards[] = array_merge($formula, ['type' => 'formula']);
        }

        return array_values(array_slice($cards, 0, 30));
    }

    private static function getFormulaBank(): array
    {
        return [
            ['front' => 'Ideal Gas Equation', 'back' => 'PV = nRT', 'subject' => 'Physics'],
            ['front' => 'Newton\'s Second Law', 'back' => 'F = ma', 'subject' => 'Physics'],
            ['front' => 'Ohm\'s Law', 'back' => 'V = IR', 'subject' => 'Physics'],
            ['front' => 'Kinematic Equation', 'back' => 'v² = u² + 2as', 'subject' => 'Physics'],
            ['front' => 'Quadratic Formula', 'back' => 'x = (-b ± √(b²-4ac)) / 2a', 'subject' => 'Maths'],
            ['front' => 'Integration by parts', 'back' => '∫u dv = uv − ∫v du', 'subject' => 'Maths'],
            ['front' => 'Molarity', 'back' => 'M = moles / volume(L)', 'subject' => 'Chemistry'],
            ['front' => 'pH Formula', 'back' => 'pH = −log[H⁺]', 'subject' => 'Chemistry'],
            ['front' => 'Cell Theory', 'back' => 'All living organisms are made of cells', 'subject' => 'Biology'],
            ['front' => 'Photosynthesis', 'back' => '6CO₂ + 6H₂O → C₆H₁₂O₆ + 6O₂', 'subject' => 'Biology'],
        ];
    }

    /**
     * Revision profile for app dashboards and weakness analysis.
     */
    public static function getRevisionProfile(int $userId): array
    {
        $dashboard = self::getDashboardData($userId);
        $analytics = self::getOrCreateAnalytics($userId);
        $weakAreas = collect(self::getMergedWeakTopics($userId, 10))->map(fn ($t) => [
            'topic' => $t['topic'],
            'subject' => $t['subject'],
            'accuracy' => round(($t['success_rate'] ?? 0) / 100, 2),
            'success_rate' => round($t['success_rate'] ?? 0, 1),
            'attempts' => $t['attempts'] ?? 0,
            'source' => $t['source'] ?? 'unknown',
        ])->values()->toArray();

        return [
            'success' => true,
            'strength_score' => (int) round($analytics->overall_accuracy ?? 0),
            'overall_accuracy' => round($analytics->overall_accuracy ?? 0, 1),
            'weak_topics' => $weakAreas,
            'revision_needed' => $dashboard['revision_needed'],
            'profile' => $dashboard['profile'],
            'saved_notes_hint' => count($weakAreas),
        ];
    }

    /**
     * Get complete analytics dashboard data for API
     */
    public static function getDashboardData(int $userId): array
    {
        $user = User::find($userId);
        $analytics = self::getOrCreateAnalytics($userId);

        return [
            'profile' => [
                'level' => $user->current_level ?? 1,
                'total_xp' => $user->total_xp ?? 0,
                'streak' => $user->current_streak ?? 0,
                'accuracy' => round($analytics->overall_accuracy ?? 0, 1),
                'questions_answered' => $analytics->total_questions ?? 0,
            ],
            'level_progress' => self::getXPForNextLevel($user->total_xp ?? 0),
            'learning_style' => $analytics->learning_style ?? 'verbal',
            'preferred_language' => $analytics->preferred_language ?? 'english',
            'weak_topics' => self::getWeakTopics($userId),
            'revision_needed' => self::getRevisionTopics($userId),
            'today_stats' => self::getTodayStats($userId),
            'peer_comparison' => self::getPeerComparison($userId),
            'achievements' => UserAchievement::getRecent($userId, 10)->toArray(),
            'achievement_count' => UserAchievement::where('user_id', $userId)->count(),
        ];
    }
}
