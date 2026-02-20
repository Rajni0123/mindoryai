<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\Cache\SmartCacheService;
use App\Services\Cache\ConversationGuard;
use App\Services\Cache\MessageClassifier;
use Illuminate\Support\Facades\Log;

/**
 * Smart Cache Middleware — Bug-Proof 4-Layer System
 *
 * LAYER 1: Conversation Guard (Is conversation active? < 10 min)
 * LAYER 2: Message Classifier (Is it continuation/greeting/context-dependent?)
 * LAYER 3: Cache Lookup (Topic-based cache check)
 * LAYER 4: Response Store Guard (Only store valid responses)
 */
class SmartCacheMiddleware
{
    private SmartCacheService $cacheService;
    private ConversationGuard $guard;
    private MessageClassifier $classifier;

    public function __construct(SmartCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
        $this->guard = app(ConversationGuard::class);
        $this->classifier = app(MessageClassifier::class);
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $sourceType = 'ai_doubt'): Response
    {
        // Only apply to POST requests with content/message field
        if (!$request->isMethod('POST')) {
            return $next($request);
        }

        // Get the question/content from request
        $question = $this->extractQuestion($request);
        if (empty($question)) {
            return $next($request);
        }

        // Get chat ID for conversation tracking
        $chatId = $request->input('chat_id') ?? $request->route('chatId');

        // ═══════════════════════════════════════════════════════════════
        // LAYER 1: Conversation Active Check (Most Important!)
        // If conversation is active (< 10 min), ALWAYS skip cache → go to AI
        // ═══════════════════════════════════════════════════════════════
        if ($chatId && $this->guard->isConversationActive((int)$chatId)) {
            Log::info("[SmartCache] BLOCKED - Layer1: Active conversation", [
                'chat_id' => $chatId,
                'message' => mb_substr($question, 0, 50),
                'last_topic' => $this->guard->getLastTopic((int)$chatId),
            ]);
            return $next($request); // Skip cache, go to AI with full history
        }

        // ═══════════════════════════════════════════════════════════════
        // LAYER 2: Message Classification
        // Block continuations, greetings, context-dependent messages
        // ═══════════════════════════════════════════════════════════════
        $msgType = $this->classifier->classify($question);

        if ($msgType !== 'standalone') {
            Log::info("[SmartCache] BLOCKED - Layer2: {$msgType}", [
                'message' => $question,
                'chat_id' => $chatId,
            ]);
            return $next($request); // Skip cache, go to AI
        }

        // ═══════════════════════════════════════════════════════════════
        // LAYER 3: Cache Lookup (Only if Layer 1 & 2 pass)
        // ═══════════════════════════════════════════════════════════════

        // Check if this is a first message (no conversation history)
        $isFirstMessage = $this->isFirstMessage($request);

        // Get optional filters
        $subject = $request->input('subject');
        $examType = $request->input('exam_type') ?? $request->input('examType');

        // Start timing
        $startTime = microtime(true);

        // Check cache
        $cacheResult = $this->cacheService->lookup(
            $question,
            $sourceType,
            $subject,
            $examType,
            $isFirstMessage
        );

        $lookupTime = round((microtime(true) - $startTime) * 1000, 2);

        // If cache hit, return cached response immediately
        if ($cacheResult['hit']) {
            Log::info("[SmartCache] Cache HIT in {$lookupTime}ms", [
                'source_type' => $sourceType,
                'match_level' => $cacheResult['match_level'],
                'question' => substr($question, 0, 50),
            ]);

            return response()->json([
                'success' => true,
                'response' => $cacheResult['answer'],
                'cached' => true,
                'cache_match' => $cacheResult['match_level'],
                'lookup_ms' => $lookupTime,
            ]);
        }

        // Cache miss - proceed with request
        Log::debug("[SmartCache] Cache MISS ({$cacheResult['reason']})", [
            'source_type' => $sourceType,
            'question' => substr($question, 0, 50),
        ]);

        // Store question info in request for post-processing
        $request->attributes->set('_smart_cache_question', $question);
        $request->attributes->set('_smart_cache_source_type', $sourceType);
        $request->attributes->set('_smart_cache_subject', $subject);
        $request->attributes->set('_smart_cache_exam_type', $examType);
        $request->attributes->set('_smart_cache_is_first', $isFirstMessage);

        // Let request proceed
        $response = $next($request);

        // ═══════════════════════════════════════════════════════════════
        // LAYER 4: Response Store Guard (After AI response)
        // Only store valid, non-context-dependent responses
        // ═══════════════════════════════════════════════════════════════
        $this->cacheResponse($request, $response);

        return $response;
    }

    /**
     * Extract question from request
     */
    private function extractQuestion(Request $request): ?string
    {
        // Try common field names
        $question = $request->input('content')
            ?? $request->input('message')
            ?? $request->input('question')
            ?? $request->input('query')
            ?? $request->input('text');

        return $question ? trim($question) : null;
    }

    /**
     * Check if this is the first message in conversation
     */
    private function isFirstMessage(Request $request): bool
    {
        // Check various indicators
        $conversationHistory = $request->input('conversation_history')
            ?? $request->input('history')
            ?? $request->input('messages')
            ?? [];

        if (is_string($conversationHistory)) {
            $conversationHistory = json_decode($conversationHistory, true) ?? [];
        }

        // If no conversation ID or empty history, it's first message
        $conversationId = $request->input('conversation_id') ?? $request->input('conversationId');
        $isNewConversation = $request->input('new_conversation') ?? $request->input('isNewConversation') ?? false;

        return empty($conversationHistory) || empty($conversationId) || $isNewConversation;
    }

    /**
     * Cache the response if successful (with Layer 4 guards)
     */
    private function cacheResponse(Request $request, Response $response): void
    {
        // Only cache successful responses
        if ($response->getStatusCode() !== 200) {
            return;
        }

        // Get stored request info
        $question = $request->attributes->get('_smart_cache_question');
        $sourceType = $request->attributes->get('_smart_cache_source_type');
        $subject = $request->attributes->get('_smart_cache_subject');
        $examType = $request->attributes->get('_smart_cache_exam_type');
        $isFirstMessage = $request->attributes->get('_smart_cache_is_first');

        if (empty($question)) {
            return;
        }

        // Parse response content
        $content = $response->getContent();
        $data = json_decode($content, true);

        if (!$data || !isset($data['success']) || !$data['success']) {
            return;
        }

        // Extract answer from response
        $answer = $data['response']
            ?? $data['answer']
            ?? $data['message']
            ?? $data['content']
            ?? null;

        if (empty($answer)) {
            return;
        }

        // ═══════════════════════════════════════════════════════════════
        // LAYER 4: Response Store Guard — Don't store context-dependent responses
        // ═══════════════════════════════════════════════════════════════
        $skipStorePatterns = [
            '/as I (said|mentioned|explained)/i',
            '/jaise maine bataya/i',
            '/continuing from/i',
            '/building on/i',
            '/as discussed/i',
            '/earlier I said/i',
            '/pehle bataya/i',
        ];

        foreach ($skipStorePatterns as $pattern) {
            if (preg_match($pattern, $answer)) {
                Log::info("[SmartCache] NOT STORED: Context-dependent response", [
                    'pattern' => $pattern,
                    'question' => mb_substr($question, 0, 50),
                ]);
                return;
            }
        }

        // Don't store very short responses
        $minResponseLength = config('smartcache.min_response_length_to_store', 100);
        if (mb_strlen($answer) < $minResponseLength) {
            Log::info("[SmartCache] NOT STORED: Response too short", [
                'length' => mb_strlen($answer),
                'min_required' => $minResponseLength,
            ]);
            return;
        }

        // Don't cache if response indicates it shouldn't be cached
        if (isset($data['no_cache']) && $data['no_cache']) {
            return;
        }

        // Get additional metadata
        $topic = $data['topic'] ?? null;
        $classLevel = $data['class_level'] ?? $request->input('class_level') ?? null;
        $tokenCount = $data['token_count'] ?? $data['tokens_used'] ?? 0;

        // Store in cache
        try {
            $stored = $this->cacheService->store(
                $question,
                $answer,
                $sourceType,
                $subject,
                $topic,
                $classLevel,
                $examType,
                $tokenCount
            );

            if ($stored) {
                Log::debug("[SmartCache] Cached response", [
                    'source_type' => $sourceType,
                    'question' => substr($question, 0, 50),
                ]);
            }
        } catch (\Exception $e) {
            Log::warning("[SmartCache] Failed to cache response", [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
