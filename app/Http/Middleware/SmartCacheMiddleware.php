<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\Cache\SmartCacheService;
use Illuminate\Support\Facades\Log;

/**
 * Smart Cache Middleware
 *
 * Intercepts AI requests, checks cache first, returns cached response if available.
 * If not in cache, lets request proceed and caches the response afterward.
 */
class SmartCacheMiddleware
{
    private SmartCacheService $cacheService;

    public function __construct(SmartCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
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

        // =============================================
        // CRITICAL FIX: Block short messages and continuation keywords
        // These should NEVER be cached - they need conversation context!
        // =============================================
        $questionLower = strtolower(trim($question));
        $shortBlocked = mb_strlen($question) <= 20;
        $continuationWords = ['yes', 'no', 'ok', 'haan', 'ha', 'hmm', 'sure', 'continue',
            'explain', 'details', 'more', 'aur batao', 'aage', 'yeah', 'yep', 'okay',
            'accha', 'acha', 'theek', 'thik', 'sahi', 'ji', 'haanji', 'thanks', 'thanku',
            'wow', 'nice', 'great', 'good', 'cool', 'awesome'];
        $isContinuation = in_array($questionLower, $continuationWords) ||
            preg_match('/^(yes|ok|ha+n?|sure|continue|details|more|hmm+)[\s\.\!\?]*$/i', $questionLower);

        if ($shortBlocked || $isContinuation) {
            Log::info("[SmartCacheMiddleware] SKIPPED - short/continuation message", [
                'question' => $question,
                'short_blocked' => $shortBlocked,
                'is_continuation' => $isContinuation,
            ]);
            return $next($request);  // Skip cache, go to controller
        }

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
            Log::info("[SmartCacheMiddleware] Cache HIT in {$lookupTime}ms", [
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
        Log::debug("[SmartCacheMiddleware] Cache MISS ({$cacheResult['reason']})", [
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

        // After response, try to cache it (if successful)
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
     * Cache the response if successful
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
                Log::debug("[SmartCacheMiddleware] Cached response", [
                    'source_type' => $sourceType,
                    'question' => substr($question, 0, 50),
                ]);
            }
        } catch (\Exception $e) {
            Log::warning("[SmartCacheMiddleware] Failed to cache response", [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
