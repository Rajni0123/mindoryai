<?php

namespace App\Services\Cache;

use Carbon\Carbon;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class ConversationGuard
{
    private const ACTIVE_WINDOW = 600; // 10 minutes in seconds

    /**
     * MAIN METHOD: Is conversation active?
     * If YES → cache should be SKIPPED
     *
     * NOTE: This method fails gracefully if Redis is unavailable.
     */
    public function isConversationActive(int $chatId): bool
    {
        try {
            $lastActivity = Redis::get("chat:{$chatId}:last_activity");

            if (!$lastActivity) {
                return false; // No history → fresh conversation
            }

            $lastTime = Carbon::createFromTimestamp($lastActivity);
            return $lastTime->diffInSeconds(now()) < self::ACTIVE_WINDOW;
        } catch (\Exception $e) {
            // Redis unavailable - fail open (allow cache lookup)
            Log::debug('ConversationGuard: Redis unavailable for isConversationActive', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Record every message (user + AI both)
     *
     * NOTE: This method fails silently if Redis is unavailable.
     */
    public function recordActivity(int $chatId, string $role, string $content): void
    {
        try {
            Redis::setex("chat:{$chatId}:last_activity", self::ACTIVE_WINDOW, now()->timestamp);
            Redis::setex("chat:{$chatId}:last_role", self::ACTIVE_WINDOW, $role);
            Redis::setex("chat:{$chatId}:last_topic", self::ACTIVE_WINDOW, mb_substr($content, 0, 100));
        } catch (\Exception $e) {
            // Redis unavailable - fail silently (non-critical feature)
            Log::debug('ConversationGuard: Redis unavailable for recordActivity', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get last topic (for logging/debugging)
     *
     * NOTE: This method fails gracefully if Redis is unavailable.
     */
    public function getLastTopic(int $chatId): ?string
    {
        try {
            return Redis::get("chat:{$chatId}:last_topic");
        } catch (\Exception $e) {
            Log::debug('ConversationGuard: Redis unavailable for getLastTopic', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Clear conversation state (on new chat start)
     *
     * NOTE: This method fails silently if Redis is unavailable.
     */
    public function clearState(int $chatId): void
    {
        try {
            Redis::del([
                "chat:{$chatId}:last_activity",
                "chat:{$chatId}:last_role",
                "chat:{$chatId}:last_topic",
            ]);
        } catch (\Exception $e) {
            Log::debug('ConversationGuard: Redis unavailable for clearState', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
