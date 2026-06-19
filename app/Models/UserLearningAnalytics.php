<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLearningAnalytics extends Model
{
    protected $table = 'user_learning_analytics';

    protected $fillable = [
        'user_id',
        'learning_style',
        'diagram_requests',
        'example_requests',
        'practical_questions',
        'theory_questions',
        'total_questions',
        'correct_answers',
        'overall_accuracy',
        'last_10_correct',
        'last_10_accuracy',
        'preferred_language',
        'hindi_messages',
        'hinglish_messages',
        'english_messages',
    ];

    protected $casts = [
        'overall_accuracy' => 'decimal:2',
        'last_10_accuracy' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Detect dominant learning style based on interaction patterns
     */
    public function detectLearningStyle(): string
    {
        $styles = [
            'visual' => $this->diagram_requests * 3,
            'verbal' => $this->example_requests * 3,
            'practical' => $this->practical_questions * 2,
            'theoretical' => $this->theory_questions * 2,
        ];

        // Return style with highest score, default to 'verbal'
        arsort($styles);
        $dominant = array_key_first($styles);

        // Only set if there's meaningful data
        if ($styles[$dominant] < 5) {
            return 'verbal'; // Default
        }

        return $dominant;
    }

    /**
     * Get performance tier based on recent accuracy
     */
    public function getPerformanceTier(): string
    {
        $accuracy = $this->last_10_accuracy ?? $this->overall_accuracy ?? 0;

        if ($accuracy < 40) {
            return 'struggling';
        } elseif ($accuracy > 80) {
            return 'high_performer';
        }
        return 'steady';
    }

    /**
     * Detect preferred language from message history
     */
    public function detectPreferredLanguage(): string
    {
        $total = $this->hindi_messages + $this->hinglish_messages + $this->english_messages;
        if ($total < 5) {
            return 'english';
        }

        if ($this->hindi_messages > $this->hinglish_messages && $this->hindi_messages > $this->english_messages) {
            return 'hindi';
        }
        if ($this->hinglish_messages > $this->hindi_messages && $this->hinglish_messages > $this->english_messages) {
            return 'hinglish';
        }
        return 'english';
    }
}
