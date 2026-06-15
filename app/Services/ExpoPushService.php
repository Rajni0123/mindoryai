<?php

namespace App\Services;

use App\Models\PushToken;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExpoPushService
{
    private const EXPO_PUSH_URL = 'https://exp.host/--/api/v2/push/send';

    /**
     * Send push notification to a user
     */
    public function sendToUser(User $user, string $title, string $body, array $data = []): array
    {
        $tokens = PushToken::where('user_id', $user->id)
            ->active()
            ->pluck('token')
            ->toArray();

        if (empty($tokens)) {
            return ['success' => false, 'message' => 'No active push tokens'];
        }

        return $this->sendToTokens($tokens, $title, $body, $data);
    }

    /**
     * Send push notification to multiple users
     */
    public function sendToUsers(array $userIds, string $title, string $body, array $data = []): array
    {
        $tokens = PushToken::whereIn('user_id', $userIds)
            ->active()
            ->pluck('token')
            ->toArray();

        if (empty($tokens)) {
            return ['success' => false, 'message' => 'No active push tokens'];
        }

        return $this->sendToTokens($tokens, $title, $body, $data);
    }

    /**
     * Send push notification to specific tokens
     */
    public function sendToTokens(array $tokens, string $title, string $body, array $data = []): array
    {
        $messages = [];

        foreach ($tokens as $token) {
            $messages[] = [
                'to' => $token,
                'sound' => 'blinkstudy_notification.wav', // Custom sound
                'title' => $title,
                'body' => $body,
                'data' => array_merge($data, ['type' => 'fun_notification']),
                'priority' => 'high',
                'channelId' => 'blinkstudy-fun', // Custom channel
                'badge' => 1,
            ];
        }

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post(self::EXPO_PUSH_URL, $messages);

            $result = $response->json();

            Log::info('Expo push notification sent', [
                'tokens_count' => count($tokens),
                'response' => $result,
            ]);

            // Handle failed tokens (deactivate them)
            if (isset($result['data'])) {
                foreach ($result['data'] as $index => $item) {
                    if (isset($item['status']) && $item['status'] === 'error') {
                        $failedToken = $tokens[$index] ?? null;
                        if ($failedToken && isset($item['details']['error'])) {
                            $error = $item['details']['error'];
                            if (in_array($error, ['DeviceNotRegistered', 'InvalidCredentials'])) {
                                PushToken::where('token', $failedToken)->update(['is_active' => false]);
                                Log::info('Deactivated push token', ['token' => substr($failedToken, 0, 20) . '...', 'error' => $error]);
                            }
                        }
                    }
                }
            }

            return [
                'success' => true,
                'sent_count' => count($tokens),
                'response' => $result,
            ];
        } catch (\Exception $e) {
            Log::error('Expo push notification failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send fun notification to user based on their education level
     */
    public function sendFunNotification(User $user): array
    {
        $funService = app(FunNotificationService::class);
        $notification = $funService->getSmartNotification($user);

        return $this->sendToUser(
            $user,
            $notification['title'],
            $notification['body'],
            ['notification_type' => 'fun_study_reminder']
        );
    }

    /**
     * Send notification to all users (broadcast)
     */
    public function broadcast(string $title, string $body, array $data = []): array
    {
        $tokens = PushToken::active()->pluck('token')->toArray();

        if (empty($tokens)) {
            return ['success' => false, 'message' => 'No active push tokens'];
        }

        // Expo allows max 100 notifications per request
        $chunks = array_chunk($tokens, 100);
        $results = [];

        foreach ($chunks as $chunk) {
            $results[] = $this->sendToTokens($chunk, $title, $body, $data);
        }

        return [
            'success' => true,
            'chunks_sent' => count($chunks),
            'total_tokens' => count($tokens),
        ];
    }

    /**
     * Validate Expo push token format
     */
    public function isValidToken(string $token): bool
    {
        return str_starts_with($token, 'ExponentPushToken[') && str_ends_with($token, ']');
    }
}
