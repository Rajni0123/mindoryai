<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Display a listing of notifications
     */
    public function index()
    {
        $notifications = Notification::with(['user', 'creator'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.notifications.index', compact('notifications'));
    }

    /**
     * Show the form for creating a new notification
     */
    public function create()
    {
        $users = User::where('role', 'user')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('admin.notifications.create', compact('users'));
    }

    /**
     * Store a newly created notification
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'required|in:info,success,warning,error',
            'send_to' => 'required|in:all,specific',
            'user_ids' => 'required_if:send_to,specific|array',
            'user_ids.*' => 'exists:users,id',
            'scheduled_at' => 'nullable|date|after:now',
        ]);

        $isGlobal = $validated['send_to'] === 'all';
        $scheduledAt = $validated['scheduled_at'] ?? null;

        if ($isGlobal) {
            // Send to all users
            Notification::create([
                'title' => $validated['title'],
                'message' => $validated['message'],
                'type' => $validated['type'],
                'is_global' => true,
                'created_by' => Auth::id(),
                'scheduled_at' => $scheduledAt,
            ]);

            $message = 'Notification sent to all users successfully';
        } else {
            // Send to specific users
            foreach ($validated['user_ids'] as $userId) {
                Notification::create([
                    'user_id' => $userId,
                    'title' => $validated['title'],
                    'message' => $validated['message'],
                    'type' => $validated['type'],
                    'is_global' => false,
                    'created_by' => Auth::id(),
                    'scheduled_at' => $scheduledAt,
                ]);
            }

            $count = count($validated['user_ids']);
            $message = "Notification sent to {$count} user(s) successfully";
        }

        return redirect()->route('admin.notifications.index')
            ->with('success', $message);
    }

    /**
     * Display the specified notification
     */
    public function show(Notification $notification)
    {
        $notification->load(['user', 'creator']);
        return view('admin.notifications.show', compact('notification'));
    }

    /**
     * Remove the specified notification
     */
    public function destroy(Notification $notification)
    {
        $notification->delete();

        return redirect()->route('admin.notifications.index')
            ->with('success', 'Notification deleted successfully');
    }

    /**
     * Get notification statistics
     */
    public function stats()
    {
        $stats = [
            'total' => Notification::count(),
            'unread' => Notification::unread()->count(),
            'global' => Notification::where('is_global', true)->count(),
            'today' => Notification::whereDate('created_at', today())->count(),
        ];

        return response()->json($stats);
    }
}
