<?php

namespace App\Services;

use App\Models\ChatConversation;
use App\Models\ChatRequest;
use App\Models\PublicDoubt;
use App\Models\StudentWallet;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TopperConnectService
{
    protected TokenWalletService $walletService;

    public function __construct(TokenWalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * Get available toppers for a subject
     */
    public function getAvailableToppers(string $subject = null, int $limit = 20): Collection
    {
        $query = User::where('is_topper', true)
            ->where('is_verified_topper', true)
            ->where('topper_available', true)
            ->where('is_active', true);

        if ($subject) {
            $query->where(function ($q) use ($subject) {
                $q->where('topper_specialization', $subject)
                  ->orWhereJsonContains('topper_subjects', $subject);
            });
        }

        return $query->orderByDesc('topper_rating')
                     ->orderByDesc('topper_total_chats')
                     ->limit($limit)
                     ->get()
                     ->map(function ($topper) {
                         return [
                             'id' => $topper->id,
                             'name' => $topper->name,
                             'exam' => $topper->topper_exam,
                             'rank' => $topper->topper_rank,
                             'rating' => $topper->topper_rating,
                             'total_chats' => $topper->topper_total_chats,
                             'specialization' => $topper->topper_specialization,
                             'subjects' => $topper->topper_subjects,
                             'bio' => $topper->topper_bio,
                             'is_online' => $this->isTopperOnline($topper),
                         ];
                     });
    }

    /**
     * Get featured toppers
     */
    public function getFeaturedToppers(int $limit = 5): Collection
    {
        return User::where('is_topper', true)
            ->where('is_verified_topper', true)
            ->where('is_active', true)
            ->where('topper_rating', '>=', 4.5)
            ->orderByDesc('topper_total_chats')
            ->limit($limit)
            ->get()
            ->map(function ($topper) {
                return [
                    'id' => $topper->id,
                    'name' => $topper->name,
                    'exam' => $topper->topper_exam,
                    'rank' => $topper->topper_rank,
                    'rating' => $topper->topper_rating,
                    'total_chats' => $topper->topper_total_chats,
                    'specialization' => $topper->topper_specialization,
                    'is_online' => $this->isTopperOnline($topper),
                ];
            });
    }

    /**
     * Check if topper is online (active in last 5 minutes)
     */
    private function isTopperOnline(User $topper): bool
    {
        if (!$topper->topper_last_active) {
            return false;
        }
        return $topper->topper_last_active->diffInMinutes(now()) <= 5;
    }

    /**
     * Create a chat request from student to topper
     */
    public function createChatRequest(
        User $student,
        int $topperId,
        string $subject,
        string $doubtPreview
    ): array {
        // Validate topper
        $topper = User::where('id', $topperId)
            ->where('is_verified_topper', true)
            ->where('is_active', true)
            ->first();

        if (!$topper) {
            return ['success' => false, 'error' => 'Topper not found or unavailable'];
        }

        // Check student wallet balance
        $wallet = $this->walletService->getWallet($student);
        if ($wallet->token_balance < 10) {
            return ['success' => false, 'error' => 'Insufficient tokens. Minimum 10 tokens required.'];
        }

        // Check for existing pending request to same topper
        $existingRequest = ChatRequest::where('student_id', $student->id)
            ->where('topper_id', $topperId)
            ->where('status', 'pending')
            ->first();

        if ($existingRequest) {
            return ['success' => false, 'error' => 'You already have a pending request to this topper'];
        }

        try {
            $request = ChatRequest::create([
                'student_id' => $student->id,
                'topper_id' => $topperId,
                'subject' => $subject,
                'doubt_preview' => substr($doubtPreview, 0, 200),
                'student_token_balance' => $wallet->token_balance,
                'status' => 'pending',
                'expires_at' => now()->addMinutes(15), // Request expires in 15 minutes
            ]);

            return [
                'success' => true,
                'request_id' => $request->id,
                'expires_at' => $request->expires_at->toISOString(),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to create chat request', [
                'student_id' => $student->id,
                'topper_id' => $topperId,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'error' => 'Failed to create request'];
        }
    }

    /**
     * Get pending requests for a topper (priority inbox)
     */
    public function getPendingRequestsForTopper(User $topper): Collection
    {
        // Mark expired requests first
        ChatRequest::markExpired();

        return ChatRequest::where('topper_id', $topper->id)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->with('student:id,name,student_class')
            ->orderByDesc('student_token_balance') // Priority by balance
            ->orderBy('created_at')
            ->get()
            ->map(function ($request) {
                return [
                    'id' => $request->id,
                    'student' => [
                        'id' => $request->student->id,
                        'name' => $request->student->name,
                        'class' => $request->student->student_class,
                    ],
                    'subject' => $request->subject,
                    'doubt_preview' => $request->doubt_preview,
                    'token_balance' => $request->student_token_balance,
                    'priority' => $this->calculatePriority($request->student_token_balance),
                    'expires_at' => $request->expires_at->toISOString(),
                    'created_at' => $request->created_at->toISOString(),
                ];
            });
    }

    /**
     * Calculate priority level based on token balance
     */
    private function calculatePriority(int $balance): string
    {
        if ($balance >= 500) return 'high';
        if ($balance >= 200) return 'medium';
        return 'low';
    }

    /**
     * Accept a chat request
     */
    public function acceptRequest(User $topper, int $requestId): array
    {
        $request = ChatRequest::where('id', $requestId)
            ->where('topper_id', $topper->id)
            ->where('status', 'pending')
            ->first();

        if (!$request) {
            return ['success' => false, 'error' => 'Request not found or already processed'];
        }

        if ($request->isExpired()) {
            $request->update(['status' => 'expired']);
            return ['success' => false, 'error' => 'Request has expired'];
        }

        try {
            $conversation = $request->accept();

            return [
                'success' => true,
                'conversation_id' => $conversation->id,
                'student' => [
                    'id' => $request->student->id,
                    'name' => $request->student->name,
                ],
            ];
        } catch (\Exception $e) {
            Log::error('Failed to accept chat request', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'error' => 'Failed to accept request'];
        }
    }

    /**
     * Reject a chat request
     */
    public function rejectRequest(User $topper, int $requestId, ?string $reason = null): array
    {
        $request = ChatRequest::where('id', $requestId)
            ->where('topper_id', $topper->id)
            ->where('status', 'pending')
            ->first();

        if (!$request) {
            return ['success' => false, 'error' => 'Request not found or already processed'];
        }

        $request->reject($reason);

        return ['success' => true];
    }

    /**
     * Get student's active conversations
     */
    public function getStudentConversations(User $student): Collection
    {
        return ChatConversation::where('student_id', $student->id)
            ->with(['topper:id,name,topper_rating,topper_exam'])
            ->orderByDesc('updated_at')
            ->get()
            ->map(function ($conv) {
                return [
                    'id' => $conv->id,
                    'topper' => [
                        'id' => $conv->topper->id,
                        'name' => $conv->topper->name,
                        'rating' => $conv->topper->topper_rating,
                        'exam' => $conv->topper->topper_exam,
                    ],
                    'subject' => $conv->subject,
                    'status' => $conv->status,
                    'tokens_used' => $conv->tokens_used,
                    'message_count' => $conv->message_count,
                    'started_at' => $conv->started_at->toISOString(),
                    'last_message_at' => $conv->updated_at->toISOString(),
                ];
            });
    }

    /**
     * Get topper's active conversations
     */
    public function getTopperConversations(User $topper): Collection
    {
        return ChatConversation::where('topper_id', $topper->id)
            ->with(['student:id,name,student_class'])
            ->orderByDesc('updated_at')
            ->get()
            ->map(function ($conv) {
                return [
                    'id' => $conv->id,
                    'student' => [
                        'id' => $conv->student->id,
                        'name' => $conv->student->name,
                        'class' => $conv->student->student_class,
                    ],
                    'subject' => $conv->subject,
                    'status' => $conv->status,
                    'tokens_earned' => $conv->tokens_used,
                    'message_count' => $conv->message_count,
                    'started_at' => $conv->started_at->toISOString(),
                    'last_message_at' => $conv->updated_at->toISOString(),
                ];
            });
    }

    /**
     * Update topper availability status
     */
    public function updateTopperAvailability(User $topper, bool $available): bool
    {
        return $topper->update([
            'topper_available' => $available,
            'topper_last_active' => now(),
        ]);
    }

    /**
     * Get public doubts (resolved Q&A)
     */
    public function getPublicDoubts(
        ?string $subject = null,
        string $sort = 'recent',
        int $limit = 20
    ): Collection {
        $query = PublicDoubt::approved()->with(['topper:id,name,topper_rating']);

        if ($subject) {
            $query->bySubject($subject);
        }

        switch ($sort) {
            case 'popular':
                $query->popular();
                break;
            case 'trending':
                $query->trending();
                break;
            default:
                $query->orderByDesc('created_at');
        }

        return $query->limit($limit)->get()->map(function ($doubt) {
            return [
                'id' => $doubt->id,
                'subject' => $doubt->subject,
                'topic' => $doubt->topic,
                'question' => $doubt->question,
                'answer' => $doubt->answer,
                'image_url' => $doubt->image_url,
                'upvotes' => $doubt->upvotes,
                'views' => $doubt->views,
                'topper' => [
                    'id' => $doubt->topper->id,
                    'name' => $doubt->topper->name,
                    'rating' => $doubt->topper->topper_rating,
                ],
                'created_at' => $doubt->created_at->toISOString(),
            ];
        });
    }

    /**
     * Search public doubts
     */
    public function searchPublicDoubts(string $query, int $limit = 20): Collection
    {
        return PublicDoubt::approved()
            ->search($query)
            ->with(['topper:id,name,topper_rating'])
            ->limit($limit)
            ->get()
            ->map(function ($doubt) {
                return [
                    'id' => $doubt->id,
                    'subject' => $doubt->subject,
                    'question' => $doubt->question,
                    'answer' => substr($doubt->answer, 0, 200) . '...',
                    'upvotes' => $doubt->upvotes,
                    'topper' => $doubt->topper->name,
                ];
            });
    }
}
