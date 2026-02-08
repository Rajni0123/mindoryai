<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupportChat;
use App\Models\SupportMessage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SupportChatController extends Controller
{
    /**
     * Check if user has access to dedicated support (All paid plans: Lite, Pro, Ultimate)
     * Free users only get email support
     */
    private function checkAccess(Request $request): bool
    {
        $user = $request->user();
        if (!$user) {
            return false;
        }

        $planSlug = strtolower($user->plan->slug ?? $user->userPlan->slug ?? 'free');

        // All paid plans get chat support (Lite, Starter, Pro, Ultimate)
        $paidPlans = ['lite', 'starter', 'pro', 'ultimate'];

        foreach ($paidPlans as $plan) {
            if (str_contains($planSlug, $plan)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get user's plan tier for priority handling
     */
    private function getPlanTier(Request $request): string
    {
        $user = $request->user();
        $planSlug = strtolower($user->plan->slug ?? $user->userPlan->slug ?? 'free');

        if (str_contains($planSlug, 'ultimate')) return 'ultimate';
        if (str_contains($planSlug, 'pro')) return 'pro';
        if (str_contains($planSlug, 'lite') || str_contains($planSlug, 'starter')) return 'lite';
        return 'free';
    }

    /**
     * Get or create support chat and return messages
     */
    public function getChat(Request $request): JsonResponse
    {
        try {
            if (!$this->checkAccess($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chat support is available for paid plan users. Please upgrade or use email support.',
                ], 403);
            }

            $user = $request->user();
            $planTier = $this->getPlanTier($request);
            $chat = SupportChat::getOrCreateForUser($user);

            // Set priority based on plan
            if ($chat->wasRecentlyCreated || !$chat->priority) {
                $priority = match($planTier) {
                    'ultimate' => 'high',
                    'pro' => 'medium',
                    default => 'normal',
                };
                $chat->update(['priority' => $priority]);
            }

            // Mark admin messages as read when user opens chat
            $chat->messages()
                ->where('sender_type', 'admin')
                ->where('is_read', false)
                ->update(['is_read' => true, 'read_at' => now()]);

            $messages = $chat->messages()
                ->with('sender:id,name')
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(function ($msg) use ($user) {
                    return [
                        'id' => $msg->id,
                        'message' => $msg->message,
                        'sender_type' => $msg->sender_type,
                        'sender_name' => $msg->sender_type === 'admin' ? 'Support Team' : $user->name,
                        'is_mine' => $msg->sender_id === $user->id,
                        'created_at' => $msg->created_at->toIso8601String(),
                        'is_read' => $msg->is_read,
                    ];
                });

            return response()->json([
                'success' => true,
                'chat' => [
                    'id' => $chat->id,
                    'status' => $chat->status,
                    'messages' => $messages,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load chat: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send a message to support
     */
    public function sendMessage(Request $request): JsonResponse
    {
        try {
            if (!$this->checkAccess($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chat support is available for paid plan users. Please upgrade or use email support.',
                ], 403);
            }

            $request->validate([
                'message' => 'required|string|max:2000',
            ]);

            $user = $request->user();
            $chat = SupportChat::getOrCreateForUser($user);

            // Create message
            $message = SupportMessage::create([
                'support_chat_id' => $chat->id,
                'sender_id' => $user->id,
                'sender_type' => 'user',
                'message' => $request->message,
            ]);

            // Update chat status and last message time
            $chat->update([
                'status' => 'pending',
                'last_message_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => [
                    'id' => $message->id,
                    'message' => $message->message,
                    'sender_type' => 'user',
                    'sender_name' => $user->name,
                    'is_mine' => true,
                    'created_at' => $message->created_at->toIso8601String(),
                    'is_read' => false,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send message: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get unread message count for user
     */
    public function getUnreadCount(Request $request): JsonResponse
    {
        try {
            if (!$this->checkAccess($request)) {
                return response()->json([
                    'success' => true,
                    'unread_count' => 0,
                ]);
            }

            $user = $request->user();
            $chat = SupportChat::where('user_id', $user->id)
                ->where('status', '!=', 'closed')
                ->first();

            $count = 0;
            if ($chat) {
                $count = $chat->messages()
                    ->where('sender_type', 'admin')
                    ->where('is_read', false)
                    ->count();
            }

            return response()->json([
                'success' => true,
                'unread_count' => $count,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => true,
                'unread_count' => 0,
            ]);
        }
    }

    /**
     * Check if user has dedicated support access
     */
    public function checkSupportAccess(Request $request): JsonResponse
    {
        try {
            $hasAccess = $this->checkAccess($request);
            $planTier = $this->getPlanTier($request);
            $planSlug = $request->user()->plan->slug ?? $request->user()->userPlan->slug ?? 'free';

            // Support type based on plan
            $supportType = match($planTier) {
                'ultimate' => 'chat_priority',  // Priority chat support
                'pro' => 'chat',                 // Standard chat support
                'lite' => 'chat',                // Standard chat support
                default => 'email',              // Email only for free
            };

            return response()->json([
                'success' => true,
                'has_chat_support' => $hasAccess,
                'support_type' => $supportType,
                'plan' => $planSlug,
                'plan_tier' => $planTier,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => true,
                'has_chat_support' => false,
                'support_type' => 'email',
                'plan' => 'free',
                'plan_tier' => 'free',
            ]);
        }
    }
}
