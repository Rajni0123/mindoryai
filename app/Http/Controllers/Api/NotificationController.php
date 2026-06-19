<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Services\FunNotificationService;
use App\Support\ResourceAuthorizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    protected FunNotificationService $funNotificationService;

    public function __construct(FunNotificationService $funNotificationService)
    {
        $this->funNotificationService = $funNotificationService;
    }

    /**
     * Get all notifications for authenticated user
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $notifications = Notification::forUser($user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->through(function (Notification $notification) use ($user) {
                $notification->setAttribute('is_read', $notification->isReadByUser($user->id));

                return $notification;
            });

        return response()->json([
            'success' => true,
            'notifications' => $notifications,
        ]);
    }

    /**
     * Get unread notifications count
     */
    public function unreadCount()
    {
        $user = Auth::user();

        $count = Notification::forUser($user->id)
            ->unreadForUser($user->id)
            ->count();

        return response()->json([
            'success' => true,
            'count' => $count,
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
          ->header('Pragma', 'no-cache');
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($id)
    {
        $user = Auth::user();

        $notification = Notification::where('id', $id)
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('is_global', true);
            })
            ->first();

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found',
            ], 404);
        }

        if (!$notification->is_global && (int) $notification->user_id !== (int) $user->id) {
            ResourceAuthorizer::forbidden('You do not have permission to modify this notification.');
        }

        $notification->markAsReadForUser($user->id);

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read',
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        $user = Auth::user();

        Notification::markAllAsReadForUser($user->id);

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read',
        ]);
    }

    /**
     * Delete a notification
     */
    public function destroy($id)
    {
        $user = Auth::user();

        $notification = Notification::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$notification) {
            ResourceAuthorizer::forbidden('You do not have permission to delete this notification.');
        }

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted successfully',
        ]);
    }

    /**
     * Get a smart fun notification for the current user (Zomato-style)
     */
    public function getSmartNotification(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $notification = $this->funNotificationService->getSmartNotification($user);

        return response()->json([
            'success' => true,
            'notification' => $notification,
        ]);
    }

    /**
     * Get fun notification by type
     */
    public function getFunNotificationByType(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $type = $request->input('type', 'study_reminder');
        $notification = $this->funNotificationService->getNotification($user, $type);

        return response()->json([
            'success' => true,
            'notification' => $notification,
        ]);
    }

    /**
     * Get available fun notification types for user
     */
    public function getFunNotificationTypes(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $types = $this->funNotificationService->getAvailableTypes($user);

        return response()->json([
            'success' => true,
            'types' => $types,
        ]);
    }

    /**
     * Update notification preferences
     */
    public function updatePreferences(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $preferences = $request->validate([
            'study_reminders' => 'boolean',
            'streak_alerts' => 'boolean',
            'achievement_alerts' => 'boolean',
            'fun_notifications' => 'boolean',
            'quiet_hours_start' => 'nullable|integer|min:0|max:23',
            'quiet_hours_end' => 'nullable|integer|min:0|max:23',
        ]);

        $currentPreferences = $user->notification_preferences ?? [];
        $updatedPreferences = array_merge($currentPreferences, $preferences);

        $user->notification_preferences = $updatedPreferences;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Preferences updated successfully',
            'preferences' => $updatedPreferences,
        ]);
    }

    /**
     * Get notification preferences
     */
    public function getPreferences(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $defaultPreferences = [
            'study_reminders' => true,
            'streak_alerts' => true,
            'achievement_alerts' => true,
            'fun_notifications' => true,
            'quiet_hours_start' => 22,
            'quiet_hours_end' => 7,
        ];

        $preferences = array_merge($defaultPreferences, $user->notification_preferences ?? []);

        return response()->json([
            'success' => true,
            'preferences' => $preferences,
        ]);
    }
}
