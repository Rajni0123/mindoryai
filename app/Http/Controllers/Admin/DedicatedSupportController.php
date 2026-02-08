<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportChat;
use App\Models\SupportMessage;
use Illuminate\Http\Request;

class DedicatedSupportController extends Controller
{
    /**
     * Display all support chats (admin dashboard)
     */
    public function index(Request $request)
    {
        $status = $request->get('status', 'all');
        $search = $request->get('search');

        $query = SupportChat::with(['user', 'assignedAdmin'])
            ->withCount(['messages as unread_count' => function ($q) {
                $q->where('sender_type', 'user')->where('is_read', false);
            }]);

        // Filter by status
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        // Search by user name or mobile
        if ($search) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('mobile', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Order by priority and last message
        $query->orderByRaw("CASE
            WHEN status = 'pending' THEN 1
            WHEN status = 'open' THEN 2
            ELSE 3
        END")
        ->orderBy('last_message_at', 'desc');

        $chats = $query->paginate(20);

        // Get stats
        $stats = [
            'total' => SupportChat::count(),
            'pending' => SupportChat::where('status', 'pending')->count(),
            'open' => SupportChat::where('status', 'open')->count(),
            'closed' => SupportChat::where('status', 'closed')->count(),
            'unread' => SupportMessage::where('sender_type', 'user')->where('is_read', false)->count(),
        ];

        return view('admin.dedicated-support.index', compact('chats', 'stats', 'status', 'search'));
    }

    /**
     * View single chat conversation
     */
    public function show($id)
    {
        $chat = SupportChat::with(['user', 'messages.sender', 'assignedAdmin'])->findOrFail($id);

        // Mark all user messages as read
        $chat->messages()
            ->where('sender_type', 'user')
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        // Assign to current admin if not assigned
        if (!$chat->assigned_admin_id) {
            $chat->update(['assigned_admin_id' => auth()->id()]);
        }

        return view('admin.dedicated-support.show', compact('chat'));
    }

    /**
     * Send reply to user
     */
    public function reply(Request $request, $id)
    {
        $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        $chat = SupportChat::findOrFail($id);

        // Create admin message
        $message = SupportMessage::create([
            'support_chat_id' => $chat->id,
            'sender_id' => auth()->id(),
            'sender_type' => 'admin',
            'message' => $request->message,
        ]);

        // Update chat status and timestamps
        $chat->update([
            'status' => 'open',
            'last_message_at' => now(),
            'last_admin_reply_at' => now(),
            'assigned_admin_id' => auth()->id(),
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => [
                    'id' => $message->id,
                    'message' => $message->message,
                    'sender_name' => auth()->user()->name,
                    'created_at' => $message->created_at->format('M d, Y h:i A'),
                ],
            ]);
        }

        return back()->with('success', 'Reply sent successfully');
    }

    /**
     * Close chat
     */
    public function close($id)
    {
        $chat = SupportChat::findOrFail($id);
        $chat->update(['status' => 'closed']);

        return back()->with('success', 'Chat closed successfully');
    }

    /**
     * Reopen chat
     */
    public function reopen($id)
    {
        $chat = SupportChat::findOrFail($id);
        $chat->update(['status' => 'open']);

        return back()->with('success', 'Chat reopened successfully');
    }

    /**
     * Get new messages (AJAX polling)
     */
    public function getNewMessages(Request $request, $id)
    {
        $lastMessageId = $request->get('last_id', 0);

        $chat = SupportChat::findOrFail($id);

        $messages = $chat->messages()
            ->where('id', '>', $lastMessageId)
            ->with('sender:id,name')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($msg) {
                return [
                    'id' => $msg->id,
                    'message' => $msg->message,
                    'sender_type' => $msg->sender_type,
                    'sender_name' => $msg->sender_type === 'admin' ? $msg->sender->name : 'User',
                    'created_at' => $msg->created_at->format('M d, Y h:i A'),
                    'is_read' => $msg->is_read,
                ];
            });

        // Mark new user messages as read
        if ($messages->where('sender_type', 'user')->count() > 0) {
            $chat->messages()
                ->where('id', '>', $lastMessageId)
                ->where('sender_type', 'user')
                ->update(['is_read' => true, 'read_at' => now()]);
        }

        return response()->json([
            'success' => true,
            'messages' => $messages,
        ]);
    }

    /**
     * Get unread count for admin badge
     */
    public function getUnreadCount()
    {
        $count = SupportMessage::where('sender_type', 'user')
            ->where('is_read', false)
            ->count();

        return response()->json(['count' => $count]);
    }
}
