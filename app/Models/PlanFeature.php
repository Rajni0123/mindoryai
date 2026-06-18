<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanFeature extends Model
{
    use HasFactory;

    protected $fillable = [
        'plan_id',
        'feature_slug',
        'limit_type',
        'limit_value',
        'extra_info',
    ];

    protected $casts = [
        'limit_value' => 'integer',
        'extra_info' => 'array',
    ];

    // Feature slug constants
    public const FEATURE_AI_DOUBT = 'ai_doubt';
    public const FEATURE_SCAN_SOLVE = 'scan_solve';
    public const FEATURE_QUIZ = 'quiz';
    public const FEATURE_TOPIC_QUIZ_GEN = 'topic_quiz_gen';
    public const FEATURE_REASONING = 'reasoning';
    public const FEATURE_EXAM_PREP = 'exam_prep';
    public const FEATURE_DAILY_CHALLENGE = 'daily_challenge';
    public const FEATURE_WHITEBOARD_VIDEO = 'whiteboard_video';
    public const FEATURE_NOTES_DIAGRAMS = 'notes_diagrams';
    public const FEATURE_STUDENT_CHAT = 'student_chat';
    public const FEATURE_PDF_ANALYSIS_PAGES = 'pdf_analysis_pages';
    public const FEATURE_CHAT_HISTORY_DAYS = 'chat_history_days';
    public const FEATURE_LEADERBOARD = 'leaderboard';
    public const FEATURE_ANALYTICS = 'analytics';
    public const FEATURE_ADS = 'ads';
    public const FEATURE_VIDEO_QUALITY = 'video_quality';
    public const FEATURE_VIDEO_WATERMARK = 'video_watermark';
    public const FEATURE_SUPPORT = 'support';
    public const FEATURE_STUDY_RECOMMENDATIONS = 'study_recommendations';
    public const FEATURE_WEEKLY_REPORTS = 'weekly_reports';
    public const FEATURE_PARENT_DASHBOARD = 'parent_dashboard';
    public const FEATURE_CUSTOM_STUDY_SCHEDULE = 'custom_study_schedule';
    public const FEATURE_DOWNLOAD_CHATS_PDF = 'download_chats_pdf';

    // Limit type constants
    public const LIMIT_TYPE_DAILY = 'daily';
    public const LIMIT_TYPE_MONTHLY = 'monthly';
    public const LIMIT_TYPE_UNLIMITED = 'unlimited';
    public const LIMIT_TYPE_BOOLEAN = 'boolean';

    // Special limit values
    public const LIMIT_UNLIMITED = -1;
    public const LIMIT_DISABLED = 0;

    /**
     * Feature display names for user-friendly messages.
     */
    public const FEATURE_NAMES = [
        'ai_doubt' => 'AI Doubt Solving',
        'scan_solve' => 'Scan to Solve',
        'quiz' => 'Quiz',
        'topic_quiz_gen' => 'Topic Quiz Generator',
        'reasoning' => 'Reasoning Practice',
        'exam_prep' => 'Exam Prep',
        'daily_challenge' => 'Daily Challenge',
        'whiteboard_video' => 'Whiteboard Video',
        'notes_diagrams' => 'Notes with Diagrams',
        'student_chat' => 'Student Chat',
        'pdf_analysis_pages' => 'PDF Analysis',
        'chat_history_days' => 'Chat History',
        'leaderboard' => 'Leaderboard',
        'analytics' => 'Analytics',
        'ads' => 'Advertisements',
        'video_quality' => 'Video Quality',
        'video_watermark' => 'Video Watermark',
        'support' => 'Support',
        'study_recommendations' => 'Study Recommendations',
        'weekly_reports' => 'Weekly Reports',
        'parent_dashboard' => 'Parent Dashboard',
        'custom_study_schedule' => 'Custom Study Schedule',
        'download_chats_pdf' => 'Download Chats as PDF',
    ];

    /**
     * Get the plan that owns this feature.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Check if the feature is unlimited.
     */
    public function isUnlimited(): bool
    {
        return $this->limit_type === self::LIMIT_TYPE_UNLIMITED || $this->limit_value === self::LIMIT_UNLIMITED;
    }

    /**
     * Check if the feature is disabled.
     */
    public function isDisabled(): bool
    {
        return $this->limit_type === self::LIMIT_TYPE_BOOLEAN && $this->limit_value === self::LIMIT_DISABLED;
    }

    /**
     * Check if the feature is enabled (for boolean type).
     */
    public function isEnabled(): bool
    {
        if ($this->limit_type === self::LIMIT_TYPE_BOOLEAN) {
            return $this->limit_value > 0;
        }
        return true;
    }

    /**
     * Get the display name for this feature.
     */
    public function getDisplayName(): string
    {
        return self::FEATURE_NAMES[$this->feature_slug] ?? ucwords(str_replace('_', ' ', $this->feature_slug));
    }

    /**
     * Accessor for feature_name (alias for display name).
     */
    public function getFeatureNameAttribute(): string
    {
        return $this->getDisplayName();
    }

    /**
     * Accessor for is_unlimited.
     */
    public function getIsUnlimitedAttribute(): bool
    {
        return $this->isUnlimited();
    }

    /**
     * Accessor for daily_limit.
     */
    public function getDailyLimitAttribute(): ?int
    {
        return $this->limit_type === self::LIMIT_TYPE_DAILY ? $this->limit_value : null;
    }

    /**
     * Accessor for monthly_limit.
     */
    public function getMonthlyLimitAttribute(): ?int
    {
        return $this->limit_type === self::LIMIT_TYPE_MONTHLY ? $this->limit_value : null;
    }

    /**
     * Get the limit description for display.
     */
    public function getLimitDescription(): string
    {
        if ($this->isUnlimited()) {
            return 'Unlimited';
        }

        if ($this->isDisabled()) {
            return 'Not Available';
        }

        if ($this->limit_type === self::LIMIT_TYPE_BOOLEAN) {
            return $this->limit_value > 0 ? 'Available' : 'Not Available';
        }

        $suffix = $this->limit_type === self::LIMIT_TYPE_DAILY ? '/day' : '/month';
        return $this->limit_value . $suffix;
    }
}
