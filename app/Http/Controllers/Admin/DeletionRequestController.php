<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountDeletionRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class DeletionRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = AccountDeletionRequest::with(['user', 'processedBy'])
            ->orderBy('created_at', 'desc');

        if ($request->status && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $requests = $query->paginate(20);

        $stats = [
            'pending' => AccountDeletionRequest::where('status', 'pending')->count(),
            'processing' => AccountDeletionRequest::where('status', 'processing')->count(),
            'completed' => AccountDeletionRequest::where('status', 'completed')->count(),
            'rejected' => AccountDeletionRequest::where('status', 'rejected')->count(),
        ];

        return view('admin.deletion-requests.index', compact('requests', 'stats'));
    }

    public function show(AccountDeletionRequest $deletionRequest)
    {
        $deletionRequest->load(['user', 'processedBy']);

        // Get user's data summary
        $userStats = null;
        if ($deletionRequest->user) {
            $user = $deletionRequest->user;
            $userStats = [
                'chats' => Schema::hasTable('mobile_chats') ? DB::table('mobile_chats')->where('user_id', $user->id)->count() : 0,
                'messages' => Schema::hasTable('mobile_chat_messages') && Schema::hasTable('mobile_chats')
                    ? DB::table('mobile_chat_messages')
                        ->whereIn('mobile_chat_id', DB::table('mobile_chats')->where('user_id', $user->id)->pluck('id'))
                        ->count()
                    : 0,
                'quizzes' => Schema::hasTable('quiz_caches') ? DB::table('quiz_caches')->where('user_id', $user->id)->count() : 0,
                'videos' => Schema::hasTable('whiteboard_videos') ? DB::table('whiteboard_videos')->where('user_id', $user->id)->count() : 0,
            ];
        }

        return view('admin.deletion-requests.show', compact('deletionRequest', 'userStats'));
    }

    public function updateStatus(Request $request, AccountDeletionRequest $deletionRequest)
    {
        $request->validate([
            'status' => 'required|in:pending,processing,completed,rejected',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $deletionRequest->update([
            'status' => $request->status,
            'admin_notes' => $request->admin_notes,
            'processed_by' => auth()->id(),
            'processed_at' => now(),
        ]);

        return back()->with('success', 'Status updated successfully.');
    }

    public function process(AccountDeletionRequest $deletionRequest)
    {
        if ($deletionRequest->status === 'completed') {
            return back()->with('error', 'This request has already been completed.');
        }

        $user = $deletionRequest->user;
        if (!$user) {
            $deletionRequest->update([
                'status' => 'completed',
                'admin_notes' => 'User already deleted.',
                'processed_by' => auth()->id(),
                'processed_at' => now(),
            ]);
            return back()->with('success', 'Request marked as completed (user already deleted).');
        }

        try {
            DB::beginTransaction();

            // Delete user's data (check if tables exist first)
            if (Schema::hasTable('mobile_chat_messages') && Schema::hasTable('mobile_chats')) {
                DB::table('mobile_chat_messages')
                    ->whereIn('mobile_chat_id', DB::table('mobile_chats')->where('user_id', $user->id)->pluck('id'))
                    ->delete();
            }
            if (Schema::hasTable('mobile_chats')) {
                DB::table('mobile_chats')->where('user_id', $user->id)->delete();
            }
            if (Schema::hasTable('quiz_caches')) {
                DB::table('quiz_caches')->where('user_id', $user->id)->delete();
            }
            if (Schema::hasTable('whiteboard_videos')) {
                DB::table('whiteboard_videos')->where('user_id', $user->id)->delete();
            }
            if (Schema::hasTable('daily_usage_limits')) {
                DB::table('daily_usage_limits')->where('user_id', $user->id)->delete();
            }

            // Log before deletion
            Log::info('User account deleted via deletion request', [
                'request_id' => $deletionRequest->id,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_mobile' => $user->mobile,
                'deleted_by' => auth()->id(),
            ]);

            // Delete user
            $user->delete();

            // Update request status
            $deletionRequest->update([
                'status' => 'completed',
                'admin_notes' => ($deletionRequest->admin_notes ?? '') . "\nAccount and all data deleted on " . now()->format('Y-m-d H:i:s'),
                'processed_by' => auth()->id(),
                'processed_at' => now(),
            ]);

            DB::commit();

            return back()->with('success', 'User account and all data have been permanently deleted.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete user account', ['error' => $e->getMessage()]);
            return back()->with('error', 'Failed to delete account: ' . $e->getMessage());
        }
    }

    public function reject(Request $request, AccountDeletionRequest $deletionRequest)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $deletionRequest->update([
            'status' => 'rejected',
            'admin_notes' => 'Rejected: ' . $request->reason,
            'processed_by' => auth()->id(),
            'processed_at' => now(),
        ]);

        return back()->with('success', 'Request has been rejected.');
    }
}
