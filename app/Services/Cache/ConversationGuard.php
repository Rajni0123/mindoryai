<?php

namespace App\Services\Cache;

use Carbon\Carbon;
use Illuminate\Support\Facades\Redis;

class ConversationGuard
{
    private const ACTIVE_WINDOW = 600; // 10 minutes in seconds

    /**
     * MAIN METHOD: Is conversation active?
     * If YES → cache should be SKIPPED
     */
    public function isConversationActive(int $chatId): bool
    {
        $lastActivity = Redis::get("chat:{$chatId}:last_activity");

        if (!$lastActivity) {
            return false; // No history → fresh conversation
        }

        $lastTime = Carbon::createFromTimestamp($lastActivity);
        return $lastTime->diffInSeconds(now()) < self::ACTIVE_WINDOW;
    }

    /**
     * Record every message (user + AI both)
     */
    public function recordActivity(int $chatId, string $role, string $content): void
    {
        Redis::setex("chat:{$chatId}:last_activity", self::ACTIVE_WINDOW, now()->timestamp);
        Redis::setex("chat:{$chatId}:last_role", self::ACTIVE_WINDOW, $role);
        Redis::setex("chat:{$chatId}:last_topic", self::ACTIVE_WINDOW, mb_substr($content, 0, 100));
    }

    /**
     * Get last topic (for logging/debugging)
     */
    public function getLastTopic(int $chatId): ?string
    {
        return Redis::get("chat:{$chatId}:last_topic");
    }

    /**
     * Clear conversation state (on new chat start)
     */
    public function clearState(int $chatId): void
    {
        Redis::del([
            "chat:{$chatId}:last_activity",
            "chat:{$chatId}:last_role",
            "chat:{$chatId}:last_topic",
        ]);
    }
}
