<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AILearnedPreferences extends Model
{
    protected $table = 'ai_learned_preferences';

    protected $fillable = [
        'user_id',
        // Style preferences
        'prefers_simple_language',
        'prefers_detailed_explanations',
        'prefers_examples',
        'prefers_step_by_step',
        // Content preferences
        'likes_real_world_examples',
        'likes_diagrams_descriptions',
        'likes_mnemonics',
        // Communication preferences
        'prefers_hinglish',
        'prefers_english',
        'prefers_formal_tone',
        'prefers_friendly_tone',
        // Learning preferences
        'struggles_with_topics',
        'favorite_topics',
        'preferred_explanation_styles',
        // Metrics
        'total_feedbacks',
        'positive_feedbacks',
        'negative_feedbacks',
        'feedback_score',
        // Issue counts
        'too_complex_count',
        'too_simple_count',
        'wrong_answer_count',
        'missing_steps_count',
        'needs_examples_count',
    ];

    protected $casts = [
        'prefers_simple_language' => 'boolean',
        'prefers_detailed_explanations' => 'boolean',
        'prefers_examples' => 'boolean',
        'prefers_step_by_step' => 'boolean',
        'likes_real_world_examples' => 'boolean',
        'likes_diagrams_descriptions' => 'boolean',
        'likes_mnemonics' => 'boolean',
        'prefers_hinglish' => 'boolean',
        'prefers_english' => 'boolean',
        'prefers_formal_tone' => 'boolean',
        'prefers_friendly_tone' => 'boolean',
        'struggles_with_topics' => 'array',
        'favorite_topics' => 'array',
        'preferred_explanation_styles' => 'array',
        'feedback_score' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get or create preferences for user
     */
    public static function getOrCreateForUser(int $userId): self
    {
        return self::firstOrCreate(
            ['user_id' => $userId],
            [
                'prefers_simple_language' => false,
                'prefers_detailed_explanations' => false,
                'prefers_examples' => true,
                'prefers_step_by_step' => true,
                'likes_real_world_examples' => true,
                'likes_diagrams_descriptions' => true,
                'likes_mnemonics' => true,
                'prefers_hinglish' => true,
                'prefers_english' => false,
                'prefers_formal_tone' => false,
                'prefers_friendly_tone' => true,
                'struggles_with_topics' => [],
                'favorite_topics' => [],
                'preferred_explanation_styles' => ['examples', 'step-by-step'],
                'feedback_score' => 0.50,
            ]
        );
    }

    /**
     * Update feedback score
     */
    public function updateFeedbackScore(): void
    {
        if ($this->total_feedbacks > 0) {
            $this->feedback_score = round($this->positive_feedbacks / $this->total_feedbacks, 2);
            $this->save();
        }
    }

    /**
     * Add topic to struggles list
     */
    public function addStrugglingTopic(string $topic): void
    {
        $topics = $this->struggles_with_topics ?? [];
        if (!in_array($topic, $topics)) {
            $topics[] = $topic;
            $this->struggles_with_topics = array_slice($topics, -10); // Keep last 10
            $this->save();
        }
    }

    /**
     * Add topic to favorites
     */
    public function addFavoriteTopic(string $topic): void
    {
        $topics = $this->favorite_topics ?? [];
        if (!in_array($topic, $topics)) {
            $topics[] = $topic;
            $this->favorite_topics = array_slice($topics, -10); // Keep last 10
            $this->save();
        }
    }

    /**
     * Check if user is satisfied (score > 0.6)
     */
    public function isSatisfied(): bool
    {
        return $this->feedback_score >= 0.6;
    }

    /**
     * Check if user needs extra care (score < 0.4)
     */
    public function needsExtraCare(): bool
    {
        return $this->feedback_score < 0.4 && $this->total_feedbacks >= 5;
    }

    /**
     * Get dominant preference type
     */
    public function getDominantStyle(): string
    {
        if ($this->prefers_simple_language && $this->too_complex_count > 3) {
            return 'simple';
        }
        if ($this->prefers_detailed_explanations && $this->too_simple_count > 3) {
            return 'detailed';
        }
        if ($this->prefers_step_by_step && $this->missing_steps_count > 3) {
            return 'step_by_step';
        }
        if ($this->prefers_examples && $this->needs_examples_count > 3) {
            return 'examples_heavy';
        }
        return 'balanced';
    }
}
