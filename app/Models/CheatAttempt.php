<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CheatAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'conversation_id',
        'message_id',
        'type',
        'details',
        'severity',
        'action_taken',
    ];

    protected $casts = [
        'severity' => 'integer',
    ];

    // Cheat types
    const TYPE_PASTE = 'paste_detected';
    const TYPE_AI_CONTENT = 'ai_content_detected';
    const TYPE_TYPING_ANOMALY = 'typing_speed_anomaly';
    const TYPE_EXTERNAL_LINK = 'external_link_shared';
    const TYPE_CONTACT_INFO = 'contact_info_shared';
    const TYPE_ABUSIVE = 'abusive_content';

    // Actions
    const ACTION_WARNED = 'warned';
    const ACTION_MESSAGE_BLOCKED = 'message_blocked';
    const ACTION_CONVERSATION_ENDED = 'conversation_ended';
    const ACTION_ACCOUNT_SUSPENDED = 'account_suspended';
    const ACTION_NONE = 'none';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function conversation()
    {
        return $this->belongsTo(ChatConversation::class, 'conversation_id');
    }

    public function message()
    {
        return $this->belongsTo(ChatMessage::class, 'message_id');
    }

    /**
     * Scope for a specific user
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for high severity
     */
    public function scopeHighSeverity($query, int $minSeverity = 4)
    {
        return $query->where('severity', '>=', $minSeverity);
    }

    /**
     * Scope by type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Record a cheat attempt
     */
    public static function record(
        int $userId,
        string $type,
        string $details,
        int $severity = 1,
        ?int $conversationId = null,
        ?int $messageId = null
    ): self {
        $attempt = static::create([
            'user_id' => $userId,
            'conversation_id' => $conversationId,
            'message_id' => $messageId,
            'type' => $type,
            'details' => $details,
            'severity' => $severity,
            'action_taken' => self::ACTION_NONE,
        ]);

        // Determine action based on severity and history
        $action = $attempt->determineAction();
        $attempt->update(['action_taken' => $action]);

        // Execute the action
        $attempt->executeAction($action);

        return $attempt;
    }

    /**
     * Determine appropriate action based on history
     */
    private function determineAction(): string
    {
        $recentAttempts = static::forUser($this->user_id)
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        // First offense with low severity - just warn
        if ($recentAttempts <= 1 && $this->severity <= 2) {
            return self::ACTION_WARNED;
        }

        // Multiple offenses or medium severity - block message
        if ($recentAttempts <= 3 && $this->severity <= 3) {
            return self::ACTION_MESSAGE_BLOCKED;
        }

        // High severity or many offenses - end conversation
        if ($this->severity >= 4 || $recentAttempts >= 5) {
            return self::ACTION_CONVERSATION_ENDED;
        }

        // Very high severity or repeat offender - suspend
        if ($this->severity >= 5 || $recentAttempts >= 10) {
            return self::ACTION_ACCOUNT_SUSPENDED;
        }

        return self::ACTION_WARNED;
    }

    /**
     * Execute the determined action
     */
    private function executeAction(string $action): void
    {
        switch ($action) {
            case self::ACTION_CONVERSATION_ENDED:
                if ($this->conversation) {
                    $this->conversation->close();
                }
                break;

            case self::ACTION_ACCOUNT_SUSPENDED:
                $this->user->update(['is_active' => false]);
                break;
        }
    }

    /**
     * Get cheat summary for a user
     */
    public static function getSummaryForUser(int $userId): array
    {
        $attempts = static::forUser($userId);

        return [
            'total_attempts' => $attempts->count(),
            'this_week' => $attempts->where('created_at', '>=', now()->subDays(7))->count(),
            'high_severity' => $attempts->highSeverity()->count(),
            'by_type' => $attempts->selectRaw('type, COUNT(*) as count')
                                  ->groupBy('type')
                                  ->pluck('count', 'type')
                                  ->toArray(),
        ];
    }
}
