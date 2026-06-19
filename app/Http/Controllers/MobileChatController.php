<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use App\Services\UnifiedAIService;
use App\Services\FileStorageService;
use App\Services\Cache\SmartCacheService;
use App\Services\Cache\ConversationGuard;
use App\Services\UsageLimitService;
use App\Services\LearningAnalyticsService;
use App\Models\FrontendConfig;
use App\Models\AiModel;

class MobileChatController extends Controller
{
    private $aiService;
    private $fileStorageService;
    private $cacheService;
    private $usageLimitService;

    public function __construct(
        UnifiedAIService $aiService,
        FileStorageService $fileStorageService,
        SmartCacheService $cacheService,
        UsageLimitService $usageLimitService
    ) {
        $this->aiService = $aiService;
        $this->fileStorageService = $fileStorageService;
        $this->cacheService = $cacheService;
        $this->usageLimitService = $usageLimitService;
    }

    /**
     * Send message and get AI response
     *
     * Flow:
     * 1. Rate limit check (10 messages/minute)
     * 2. Credit check (1 credit per message, 2 if image attached)
     * 3. Send to AI
     * 4. Deduct credits on success
     * 5. Return response with credit info
     */
    public function sendMessage(Request $request, $chatId)
    {
        // Streaming = GPT-like instant first token (?stream=1 or Accept: text/event-stream)
        if ($request->boolean('stream') || $request->header('Accept') === 'text/event-stream') {
            return $this->sendMessageStream($request, $chatId);
        }

        $requestStartedAt = microtime(true);

        // Minimal logging for production speed
        // Full debug logs only in local environment
        if (config('app.debug')) {
            \Log::debug('Chat message', ['chat_id' => $chatId, 'has_file' => $request->hasFile('file')]);
        }

        // Get validation rules from FileStorageService if active
        $maxFileSize = $this->fileStorageService->isActive()
            ? $this->fileStorageService->getActiveStorage()->getMaxFileSize() / 1024  // Convert to KB
            : 10240; // Default 10MB

        $allowedExtensions = $this->fileStorageService->isActive()
            ? implode(',', $this->fileStorageService->getActiveStorage()->getAllowedExtensions())
            : 'jpeg,jpg,png,gif,webp,pdf';

        $request->validate([
            'content' => 'nullable|string',
            'ai_model_id' => 'nullable|integer',
            'has_image' => 'nullable|boolean', // If user attached an image
            'file' => "nullable|mimes:{$allowedExtensions}|max:{$maxFileSize}", // Dynamic validation from storage settings
            'language' => 'nullable|string|in:english,hindi,hinglish', // User's preferred language
        ]);

        $user = auth()->user();
        $content = $request->input('content', '');
        $modelId = $request->input('ai_model_id');
        $hasImage = $request->hasFile('file');

        // Get user's language preference from request (set in app settings)
        $userLanguagePref = $request->input('language', null);

        // Auto-detect language from message content
        $detectedLanguage = $this->detectLanguageFromMessage($content);

        // Use user preference if set AND detected is 'english' (meaning no explicit Hindi/Hinglish detected)
        // This ensures: "Explain babar aajam" with Hindi preference → responds in Hindi
        if ($userLanguagePref && $detectedLanguage === 'english') {
            $language = $userLanguagePref;
        } else {
            $language = $detectedLanguage;
        }

        // Track language preference for user
        if ($user && !empty($content)) {
            LearningAnalyticsService::detectAndTrackLanguage($user->id, $content);
        }

        // Verify chat exists and belongs to user
        $chat = \App\Models\MobileChat::where('id', $chatId)
            ->where('user_id', $user->id)
            ->first();

        if (!$chat) {
            return response()->json([
                'error' => 'Chat not found'
            ], 404);
        }

        // Handle file upload (image or PDF) - Upload to R2/S3 Storage
        $imageData = null;
        $imageBase64 = null;
        $fileMimeType = null;
        $fileUrl = null;

        if ($hasImage) {
            $file = $request->file('file');
            $fileMimeType = $file->getMimeType();

            // Try to upload to cloud storage (R2/S3) if configured
            if ($this->fileStorageService->isActive()) {
                try {
                    \Log::info('Uploading file to cloud storage', [
                        'filename' => $file->getClientOriginalName(),
                        'size' => $file->getSize()
                    ]);

                    $uploadResult = $this->fileStorageService->upload($file, 'uploads');

                    if ($uploadResult) {
                        // File uploaded successfully - store only URL
                        $fileUrl = $uploadResult['file_url'];

                        \Log::info('File uploaded successfully to cloud storage', [
                            'url' => $fileUrl,
                            'path' => $uploadResult['file_path']
                        ]);

                        // Create image data with URL (not Base64)
                        $imageData = [
                            'uri' => $fileUrl,  // Store only URL, no Base64
                            'type' => $uploadResult['file_type'],
                            'fileName' => $uploadResult['file_name'],
                        ];
                    } else {
                        throw new \Exception('File upload failed');
                    }

                } catch (\Exception $e) {
                    \Log::error('Cloud storage upload failed, falling back to Base64', [
                        'error' => $e->getMessage()
                    ]);

                    // Fallback to Base64 if cloud storage fails
                    $imageBase64 = base64_encode(file_get_contents($file->getRealPath()));
                    $imageData = [
                        'uri' => 'data:image/jpeg;base64,' . $imageBase64,
                        'type' => $fileMimeType,
                        'fileName' => $file->getClientOriginalName(),
                    ];
                }
            } else {
                // No active cloud storage - use Base64 (legacy)
                \Log::info('No active cloud storage, using Base64 encoding');

                $imageBase64 = base64_encode(file_get_contents($file->getRealPath()));
                $imageData = [
                    'uri' => 'data:image/jpeg;base64,' . $imageBase64,
                    'type' => $fileMimeType,
                    'fileName' => $file->getClientOriginalName(),
                ];
            }

            // Handle PDF files - extract text
            if ($fileMimeType === 'application/pdf') {
                $fileSizeMB = $file->getSize() / 1024 / 1024;

                // Check if PDF is too large (over 15MB is likely to cause memory issues)
                if ($fileSizeMB > 15) {
                    \Log::warning('PDF too large for text extraction', [
                        'file_size_mb' => round($fileSizeMB, 2),
                    ]);

                    return response()->json([
                        [
                            'id' => time(),
                            'chat_id' => $chatId,
                            'sender' => 'system',
                            'content' => "❌ This PDF is too large to process (" . round($fileSizeMB, 2) . " MB).\n\n📱 Please try:\n1. Convert PDF pages to images (JPG/PNG)\n2. Split the PDF into smaller files (under 15MB)\n3. Use a text-based PDF (not scanned images)\n\n💡 Tip: Most PDF readers can export pages as images!",
                            'error' => 'pdf_too_large',
                            'created_at' => now()->toISOString(),
                        ]
                    ], 200);
                }

                try {
                    \Log::info('PDF detected, extracting text', [
                        'file_size_mb' => round($fileSizeMB, 2),
                        'memory_limit' => ini_get('memory_limit'),
                    ]);

                    ini_set('memory_limit', '512M');
                    ini_set('max_execution_time', '60');

                    $parser = new \Smalot\PdfParser\Parser();

                    $config = new \Smalot\PdfParser\Config();
                    $config->setRetainImageContent(false);
                    $parser = new \Smalot\PdfParser\Parser([], $config);

                    $pdf = $parser->parseFile($file->getRealPath());

                    $pages = $pdf->getPages();
                    $maxPages = min(20, count($pages));
                    $extractedText = '';

                    for ($i = 0; $i < $maxPages; $i++) {
                        try {
                            $extractedText .= $pages[$i]->getText() . "\n";
                        } catch (\Exception $e) {
                            \Log::warning("Failed to extract page $i", ['error' => $e->getMessage()]);
                            continue;
                        }
                    }

                    $extractedText = preg_replace('/\s+/', ' ', $extractedText);
                    $extractedText = trim($extractedText);

                    if (!empty($extractedText)) {
                        $maxChars = 8000;
                        if (strlen($extractedText) > $maxChars) {
                            $extractedText = substr($extractedText, 0, $maxChars) . '...';
                        }

                        $pdfPrefix = "📄 PDF Content:\n\n";
                        $content = empty($content)
                            ? $pdfPrefix . $extractedText
                            : $content . "\n\n" . $pdfPrefix . $extractedText;

                        $imageData = null;
                        $hasImage = false;

                        \Log::info('PDF text extracted successfully', [
                            'text_length' => strlen($extractedText),
                            'preview' => substr($extractedText, 0, 100)
                        ]);
                    } else {
                        \Log::warning('PDF text extraction returned empty - will use Gemini for PDF processing');
                    }

                } catch (\Exception $e) {
                    \Log::error('PDF text extraction failed', [
                        'error' => $e->getMessage(),
                        'file_size_mb' => round($file->getSize() / 1024 / 1024, 2),
                        'error_class' => get_class($e),
                    ]);

                    if (isset($originalMemoryLimit)) {
                        ini_set('memory_limit', $originalMemoryLimit);
                    }
                    if (isset($originalTimeLimit)) {
                        ini_set('max_execution_time', $originalTimeLimit);
                    }

                    $errorMsg = strtolower($e->getMessage());
                    if (strpos($errorMsg, 'memory') !== false ||
                        strpos($errorMsg, 'exhausted') !== false ||
                        strpos($errorMsg, 'allocation') !== false) {

                        return response()->json([
                            [
                                'id' => time(),
                                'chat_id' => $chatId,
                                'sender' => 'system',
                                'content' => "❌ This PDF is too complex to process (" . round($file->getSize() / 1024 / 1024, 2) . " MB).\n\n🔧 Please try:\n\n1. **Convert to Images**: Use your PDF reader to export pages as JPG/PNG images\n2. **Split the File**: Break the PDF into smaller chunks (5-10 pages each)\n3. **Reduce Quality**: Re-save the PDF with lower quality/compression\n4. **Remove Images**: If possible, use text-only version\n\n💡 Tip: Scanned PDFs with many images use more memory!",
                                'error' => 'pdf_memory_exhausted',
                                'created_at' => now()->toISOString(),
                            ]
                        ], 200);
                    }

                    return response()->json([
                        [
                            'id' => time(),
                            'chat_id' => $chatId,
                            'sender' => 'system',
                            'content' => "❌ Failed to process PDF: " . $e->getMessage() . "\n\n📱 Please try converting the PDF to images (JPG/PNG) instead.",
                            'error' => 'pdf_processing_failed',
                            'created_at' => now()->toISOString(),
                        ]
                    ], 200);
                }
            }
        }

        // Default AI model: prefer GPT-4o-mini (fast) when user hasn't picked one
        if (!$modelId) {
            $defaultModel = AiModel::where('provider', 'openai')
                ->where('model_identifier', config('ai.chat_model', 'gpt-4o-mini'))
                ->where('is_active', true)
                ->first();

            if (!$defaultModel) {
                $defaultModel = AiModel::where('provider', 'openai')
                    ->where('is_active', true)
                    ->orderBy('order')
                    ->first();
            }

            if ($defaultModel) {
                $modelId = $defaultModel->id;
            }
        }

        // PDF with URL still prefers Gemini when OpenAI cannot read remote PDF URLs
        if ($imageData && isset($imageData['type']) && $imageData['type'] === 'application/pdf') {
            \Log::info('PDF detected with URL - switching to Gemini for PDF URL support');

            $geminiModel = \App\Models\AiModel::where('provider', 'google')
                ->where('is_active', true)
                ->orderBy('id', 'desc')
                ->first();

            if ($geminiModel) {
                $modelId = $geminiModel->id;
                \Log::info('Switched to Gemini model for PDF processing', [
                    'model_id' => $modelId,
                    'model_name' => $geminiModel->name,
                    'reason' => 'PDF file requires Gemini (OpenAI does not support PDF URLs)'
                ]);
            } else {
                \Log::error('PDF requires Gemini but no active Gemini model found');

                return response()->json([
                    [
                        'id' => time(),
                        'chat_id' => $chatId,
                        'sender' => 'system',
                        'content' => "❌ PDF files require Google Gemini model, but it's not configured.\n\n🔧 Please:\n1. Enable Gemini in Admin Panel\n2. Or convert your PDF to images (JPG/PNG)",
                        'error' => 'gemini_not_available',
                        'created_at' => now()->toISOString(),
                    ]
                ], 200);
            }
        }

        // Require either content or image
        if (empty($content) && !$hasImage) {
            return response()->json([
                [
                    'id' => time(),
                    'chat_id' => $chatId,
                    'sender' => 'system',
                    'content' => '⚠️ Please provide a message or attach an image.',
                    'error' => 'empty_message',
                    'created_at' => now()->toISOString(),
                ]
            ], 200);
        }

        // =============================================
        // STEP 1: RATE LIMITING
        // =============================================
        $rateLimitKey = 'chat_message:' . $user->id;
        $maxAttempts = 10;
        $decayMinutes = 1;

        if (RateLimiter::tooManyAttempts($rateLimitKey, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            $errorMessage = [
                'id' => time(),
                'chat_id' => $chatId,
                'sender' => 'system',
                'content' => "⚠️ Too many messages. Please wait {$seconds} seconds before sending another message.",
                'error' => 'rate_limit_exceeded',
                'retry_after' => $seconds,
                'created_at' => now()->toISOString(),
            ];

            return response()->json([$errorMessage], 200);
        }

        // =============================================
        // STEP 2: INCREMENT RATE LIMITER
        // =============================================
        RateLimiter::hit($rateLimitKey, $decayMinutes * 60);

        // =============================================
        // STEP 4: CREATE USER MESSAGE
        // =============================================

        $userContent = $content;
        if (empty($content) && $hasImage) {
            $userContent = '[Image uploaded - analyzing...]';
        }

        // Save user message to database
        $userMessageRecord = \App\Models\MobileChatMessage::create([
            'mobile_chat_id' => $chatId,
            'sender' => 'user',
            'content' => $userContent,
            'image' => $imageData,
            'file_url' => $fileUrl ?? null,
            'file_name' => $hasImage ? $request->file('file')->getClientOriginalName() : null,
            'file_type' => $fileMimeType ?? null,
            'file_size' => $hasImage ? $request->file('file')->getSize() : null,
        ]);

        $userMessage = [
            'id' => $userMessageRecord->id,
            'chat_id' => $chatId,
            'sender' => 'user',
            'content' => $userContent,
            'image' => $imageData,
            'created_at' => $userMessageRecord->created_at->toIso8601String(),
        ];

        // Record user activity for smart cache conversation tracking
        try {
            app(ConversationGuard::class)->recordActivity($chatId, 'user', $userContent);
        } catch (\Exception $e) {
            // Silent fail - cache tracking is non-critical
        }

        // =============================================
        // STEP 5: GET AI RESPONSE
        // =============================================
        try {
            // Get conversation history for context
            // INCREASED: limit 6 -> 10 to prevent topic mixing when user sends quick messages
            $conversationHistory = \App\Models\MobileChatMessage::where('mobile_chat_id', $chatId)
                ->where('id', '!=', $userMessageRecord->id)
                ->select(['sender', 'content'])
                ->orderBy('id', 'desc')
                ->limit(10)  // 10 messages = 5 exchanges for better context
                ->get()
                ->reverse()
                ->values()  // CRITICAL: Reset array keys after reverse! Without this, keys are preserved and loop accesses wrong messages!
                ->map(fn($msg) => [
                    'role' => $msg->sender === 'user' ? 'user' : 'assistant',
                    'content' => $msg->sender === 'user'
                        ? substr($msg->content, 0, 300)
                        : substr($msg->content, 0, 800)
                ])
                ->toArray();

            // =============================================
            // NEW TOPIC / GREETING DETECTION
            // =============================================
            $greetingKeywords = config('smartcache.greeting_keywords', ['hello', 'hi', 'hey', 'namaste', 'namaskar', 'good morning', 'good afternoon', 'good evening', 'good night', 'hii', 'hiii', 'hiiii', 'helloo', 'hellooo']);
            $newTopicKeywords = ['new question', 'nayi question', 'naya sawal', 'different topic', 'change topic', 'kuch aur', 'something else', 'new topic'];

            $originalContent = $request->input('content', '');
            $contentLower = strtolower(trim($originalContent));

            \Log::info('GREETING CHECK DEBUG', [
                'original_content' => $originalContent,
                'content_lower' => $contentLower,
                'content_var' => $content,
                'history_count_before' => count($conversationHistory),
            ]);

            $isGreeting = in_array($contentLower, $greetingKeywords) || preg_match('/^(hi+|hey+|hello+|namaste|namaskar)\b/i', $originalContent);
            $isNewTopic = false;
            foreach ($newTopicKeywords as $keyword) {
                if (stripos($contentLower, $keyword) !== false) {
                    $isNewTopic = true;
                    break;
                }
            }

            if ($isGreeting || $isNewTopic) {
                $conversationHistory = [];
                \Log::info('Greeting/New topic detected - CLEARING HISTORY', [
                    'message' => $contentLower,
                    'is_greeting' => $isGreeting,
                    'is_new_topic' => $isNewTopic,
                ]);
            } else {
                \Log::info('NOT a greeting - keeping history', [
                    'message' => $contentLower,
                    'history_count' => count($conversationHistory),
                ]);
            }

            // =============================================
            // CONTINUATION CONTEXT FIX (SHARED HELPER)
            // =============================================
            $continuationKeywords = config('smartcache.continuation_keywords', ['yes', 'continue', 'haan', 'ha', 'ok', 'sure', 'go ahead', 'explain', 'tell me more', 'details', 'aur batao', 'aage']);
            $isContinuationRequest = !$isGreeting && !$isNewTopic && (in_array($contentLower, $continuationKeywords) || strlen($content) <= 15 && preg_match('/^(yes|ok|ha+n?|sure|continue|details|explain|more)\b/i', $content));

            if ($isContinuationRequest && count($conversationHistory) > 0) {
                $result = $this->buildContinuationContext($conversationHistory, $continuationKeywords, $content, $contentLower);
                if ($result) {
                    $content = $result;
                }
                // For continuation, check history for language preference (e.g., "in hindi")
                $historyLanguage = $this->detectLanguageFromHistory($conversationHistory);
                if ($historyLanguage === 'hindi') {
                    $language = 'hindi';
                }
            }

            \Log::info('AI Service Request', [
                'user_id' => $user->id,
                'chat_id' => $chatId,
                'model_id' => $modelId,
                'message' => substr($content, 0, 100),
                'history_count' => count($conversationHistory),
                'is_continuation' => $isContinuationRequest,
            ]);

            // =============================================
            // SMART CACHE LOOKUP (Before AI Call)
            // =============================================
            $cacheHit = null;

            $shortMessageBlocked = mb_strlen(trim($originalContent)) <= 20;

            $cacheBlockKeywords = config('smartcache.continuation_keywords', [
                'yes', 'yeah', 'yep', 'yup', 'ya', 'y', 'ok', 'okay', 'okk',
                'sure', 'right', 'correct', 'continue', 'next', 'more',
                'details', 'explain', 'hmm', 'hmmm', 'accha', 'acha', 'achha',
                'sahi', 'theek', 'thik', 'haan', 'ha', 'ji', 'haanji',
                'thanks', 'thank', 'thanku', 'thnx', 'wow', 'nice', 'great', 'good', 'cool'
            ]);
            $continuationPattern = '/^(' . implode('|', array_map(function($kw) {
                return preg_quote($kw, '/');
            }, $cacheBlockKeywords)) . ')\b/i';
            $continuationBlocked = preg_match($continuationPattern, trim($originalContent));

            if ($shortMessageBlocked || $continuationBlocked) {
                \Log::info('[SmartCache] BLOCKED by safety check', [
                    'message' => substr($originalContent, 0, 30),
                    'short_blocked' => $shortMessageBlocked,
                    'continuation_blocked' => $continuationBlocked,
                ]);
            }

            if (!$hasImage && !$isContinuationRequest && !$shortMessageBlocked && !$continuationBlocked && config('smartcache.enabled', true)) {
                $isFirstMessage = empty($conversationHistory);
                $cacheResult = $this->cacheService->lookup(
                    $originalContent,
                    'ai_doubt',
                    null,
                    null,
                    $isFirstMessage
                );

                if ($cacheResult['hit']) {
                    \Log::info('[SmartCache] HIT - returning cached response', [
                        'match_level' => $cacheResult['match_level'],
                        'entry_id' => $cacheResult['entry_id'],
                        'question' => substr($originalContent, 0, 50),
                    ]);
                    $cacheHit = $cacheResult;
                } else {
                    \Log::debug('[SmartCache] MISS', [
                        'reason' => $cacheResult['reason'],
                        'message' => $cacheResult['message'] ?? '',
                    ]);
                }
            }

            // If we have a cache hit, use it instead of calling AI
            if ($cacheHit) {
                $aiResponse = $cacheHit['answer'];

                // =============================================
                // BUG FIX #2: DO NOT create duplicate user message!
                // User message was already saved in STEP 4 above.
                // Just save the AI response.
                // =============================================

                // Save AI response
                $aiMessageRecord = \App\Models\MobileChatMessage::create([
                    'mobile_chat_id' => $chatId,
                    'sender' => 'assistant',
                    'content' => $aiResponse,
                ]);

                // Record AI activity for smart cache conversation tracking (even for cache hits)
                try {
                    app(ConversationGuard::class)->recordActivity($chatId, 'assistant', $aiResponse);
                } catch (\Exception $e) {
                    // Silent fail - cache tracking is non-critical
                }

                $aiMessage = [
                    'id' => $aiMessageRecord->id,
                    'chat_id' => $chatId,
                    'sender' => 'assistant',
                    'content' => $aiResponse,
                    'cached' => true,
                    'created_at' => $aiMessageRecord->created_at->toIso8601String(),
                    'meta' => [
                        'duration_ms' => round((microtime(true) - $requestStartedAt) * 1000),
                        'cached' => true,
                        'provider' => 'cache',
                    ],
                ];

                // Update chat
                $chat->update(['last_message_at' => now()]);
                if ($chat->title === 'New Chat' && !empty($content)) {
                    $chat->update(['title' => \App\Models\MobileChat::generateTitleFromMessage($content)]);
                }

                // Return cached response (no credit deduction)
                return response()->json([$userMessage, $aiMessage]);
            }

            // Determine feature based on content/image type
            $feature = 'chat';
            if ($imageData) {
                if (isset($imageData['type']) && $imageData['type'] === 'application/pdf') {
                    $feature = 'pdf_solve';
                } elseif (stripos($content, 'generate') !== false && stripos($content, 'question') !== false) {
                    $feature = 'mcq_generation';
                } else {
                    $feature = 'pdf_solve';
                }
            }

            if (config('app.debug')) {
                \Log::info('AI CALL DEBUG', [
                    'user_message' => $content,
                    'original_content' => $originalContent ?? $content,
                    'chat_id' => $chatId,
                    'user_id' => $user->id,
                    'history_count' => count($conversationHistory),
                    'language' => $language,
                    'feature' => $feature,
                    'cache_hit' => isset($cacheHit) && $cacheHit ? 'YES' : 'NO',
                ]);
            }

            $aiStartedAt = microtime(true);

            // Use UnifiedAIService — OpenAI/GPT first when AI_CHAT_PRIMARY_PROVIDER=openai
            $aiResult = $this->aiService->chat(
                $content,
                $modelId,
                $conversationHistory,
                null,
                $imageData,
                $feature,
                $user->id,
                $language
            );

            $aiDurationMs = round((microtime(true) - $aiStartedAt) * 1000);

            if (config('app.debug')) {
                \Log::info('AI Service Response', [
                    'user_id' => $user->id,
                    'chat_id' => $chatId,
                    'success' => $aiResult['success'] ?? false,
                    'error' => $aiResult['error'] ?? null,
                    'has_content' => isset($aiResult['content']),
                    'duration_ms' => $aiDurationMs,
                    'provider' => $aiResult['provider'] ?? null,
                ]);
            }

            if (!$aiResult['success']) {
                throw new \Exception($aiResult['error'] ?? 'AI service error');
            }

            $aiResponse = $aiResult['content'];

            // Defer cache write so user gets the reply faster
            if (!$hasImage && !$isContinuationRequest && config('smartcache.enabled', true)) {
                $cacheQuestion = $originalContent;
                $cacheAnswer = $aiResponse;
                $cacheTokens = $aiResult['tokens_used'] ?? 0;

                defer(function () use ($cacheQuestion, $cacheAnswer, $cacheTokens) {
                    try {
                        app(SmartCacheService::class)->store(
                            $cacheQuestion,
                            $cacheAnswer,
                            'ai_doubt',
                            null,
                            null,
                            null,
                            null,
                            $cacheTokens
                        );
                    } catch (\Exception $e) {
                        \Log::warning('[SmartCache] Deferred store failed', ['error' => $e->getMessage()]);
                    }
                });
            }

        } catch (\Exception $e) {
            \Log::error('AI response failed', [
                'user_id' => $user->id,
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $errorMessage = [
                'id' => time(),
                'chat_id' => $chatId,
                'sender' => 'system',
                'content' => "⚠️ AI Error: " . $e->getMessage(),
                'error' => 'ai_service_error',
                'created_at' => now()->toISOString(),
            ];

            return response()->json([$userMessage, $errorMessage]);
        }

        // =============================================
        // STEP 6: CREATE AI MESSAGE
        // =============================================

        $aiMessageRecord = \App\Models\MobileChatMessage::create([
            'mobile_chat_id' => $chatId,
            'sender' => 'assistant',
            'content' => $aiResponse,
        ]);

        // Record AI activity for smart cache conversation tracking
        try {
            app(ConversationGuard::class)->recordActivity($chatId, 'assistant', $aiResponse);
        } catch (\Exception $e) {
            // Silent fail - cache tracking is non-critical
        }

        $this->usageLimitService->recordUsage($user, 'ai_doubt');

        $aiMessage = [
            'id' => $aiMessageRecord->id,
            'chat_id' => $chatId,
            'sender' => 'assistant',
            'content' => $aiResponse,
            'created_at' => $aiMessageRecord->created_at->toIso8601String(),
            'meta' => [
                'duration_ms' => $aiDurationMs ?? null,
                'total_ms' => isset($requestStartedAt) ? round((microtime(true) - $requestStartedAt) * 1000) : null,
                'provider' => $aiResult['provider'] ?? null,
                'model' => $aiResult['model'] ?? null,
                'cached' => false,
                'streaming_available' => true,
            ],
        ];

        $chat->update([
            'last_message_at' => now(),
            'ai_model_id' => $modelId,
        ]);

        if ($chat->title === 'New Chat' && !empty($content)) {
            $generatedTitle = \App\Models\MobileChat::generateTitleFromMessage($content);
            $chat->update(['title' => $generatedTitle]);
        }

        // =============================================
        // STEP 8: RETURN RESPONSE
        // =============================================
        \Log::info('Returning messages to client', [
            'user_id' => $user->id,
            'chat_id' => $chatId,
            'message_count' => 2,
            'user_sender' => $userMessage['sender'],
            'ai_sender' => $aiMessage['sender'],
            'ai_content_length' => strlen($aiMessage['content']),
        ]);

        return response()->json([$userMessage, $aiMessage]);
    }

    /**
     * Get chat messages - OPTIMIZED
     */
    public function getMessages($chatId)
    {
        $user = auth()->user();

        $chatExists = \App\Models\MobileChat::where('id', $chatId)
            ->where('user_id', $user->id)
            ->exists();

        if (!$chatExists) {
            return response()->json(['error' => 'Chat not found'], 404);
        }

        $messages = \App\Models\MobileChatMessage::where('mobile_chat_id', $chatId)
            ->select(['id', 'mobile_chat_id', 'sender', 'content', 'image', 'created_at'])
            ->orderBy('id', 'asc')
            ->get()
            ->map(function ($message) {
                return [
                    'id' => $message->id,
                    'chat_id' => $message->mobile_chat_id,
                    'sender' => $message->sender,
                    'content' => $message->content,
                    'image' => $message->image,
                    'created_at' => $message->created_at->toIso8601String(),
                ];
            });

        return response()->json($messages);
    }

    /**
     * Get all chats - OPTIMIZED
     */
    public function getChats()
    {
        $user = auth()->user();

        $chats = \App\Models\MobileChat::where('user_id', $user->id)
            ->select(['id', 'title', 'created_at', 'updated_at', 'last_message_at'])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->map(function ($chat) {
                return [
                    'id' => $chat->id,
                    'title' => $chat->title,
                    'created_at' => $chat->created_at->toIso8601String(),
                    'updated_at' => $chat->updated_at->toIso8601String(),
                    'last_message_at' => $chat->last_message_at ? $chat->last_message_at->toIso8601String() : null,
                ];
            });

        return response()->json($chats);
    }

    /**
     * Create new chat
     */
    public function createChat(Request $request)
    {
        $user = auth()->user();

        $chat = \App\Models\MobileChat::create([
            'user_id' => $user->id,
            'title' => $request->input('title', 'New Chat'),
            'ai_model_id' => null,
            'last_message_at' => null,
        ]);

        return response()->json([
            'id' => $chat->id,
            'title' => $chat->title,
            'created_at' => $chat->created_at->toIso8601String(),
        ]);
    }

    /**
     * Delete chat
     */
    public function deleteChat($chatId)
    {
        $user = auth()->user();

        $chat = \App\Models\MobileChat::where('id', $chatId)
            ->where('user_id', $user->id)
            ->first();

        if (!$chat) {
            return response()->json(['error' => 'Chat not found'], 404);
        }

        $chat->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Rename chat
     */
    public function renameChat(Request $request, $chatId)
    {
        $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $user = auth()->user();

        $chat = \App\Models\MobileChat::where('id', $chatId)
            ->where('user_id', $user->id)
            ->first();

        if (!$chat) {
            return response()->json(['error' => 'Chat not found'], 404);
        }

        $chat->update(['title' => $request->input('title')]);

        return response()->json([
            'id' => $chatId,
            'title' => $chat->title,
            'updated_at' => $chat->updated_at->toIso8601String(),
        ]);
    }

    // =============================================
    // SHARED HELPER: Build Continuation Context
    // =============================================
    // BUG FIX #1: This was duplicated in sendMessage() and sendMessageStream()
    // with DIFFERENT thresholds (strlen >= 3 vs strlen > 15).
    // Now unified in one place with correct threshold.
    // =============================================
    private function buildContinuationContext(array $conversationHistory, array $continuationKeywords, string $content, string $contentLower): ?string
    {
        // =============================================
        // RACE CONDITION SAFETY CHECK
        // =============================================
        // If the LAST message in history is from USER, the AI hasn't responded yet.
        // This means user sent "Yes" too quickly before previous response was saved.
        // In this case, DON'T treat as continuation - let it be a normal message.
        if (!empty($conversationHistory)) {
            $lastMsg = end($conversationHistory);
            if ($lastMsg['role'] === 'user') {
                \Log::warning('Continuation BLOCKED - race condition detected', [
                    'reason' => 'Last message is from user, AI response not saved yet',
                    'last_msg' => substr($lastMsg['content'], 0, 30),
                ]);
                return null;  // Don't build continuation context
            }
        }

        $lastUserQuestion = null;
        $lastAssistantMessage = null;

        // DEBUG: Log the entire history array to see order
        \Log::info('CONTINUATION DEBUG - History array', [
            'history_count' => count($conversationHistory),
            'messages' => array_map(function($m, $i) {
                return "[$i] {$m['role']}: " . substr($m['content'], 0, 30);
            }, $conversationHistory, array_keys($conversationHistory)),
        ]);

        // Find the last assistant message
        for ($i = count($conversationHistory) - 1; $i >= 0; $i--) {
            if ($conversationHistory[$i]['role'] === 'assistant') {
                $lastAssistantMessage = $conversationHistory[$i]['content'];
                \Log::info('CONTINUATION DEBUG - Found last assistant', [
                    'index' => $i,
                    'preview' => substr($lastAssistantMessage, 0, 50),
                ]);
                break;
            }
        }

        // Find the last REAL user question (skip continuation keywords)
        for ($i = count($conversationHistory) - 1; $i >= 0; $i--) {
            if ($conversationHistory[$i]['role'] === 'user') {
                $userMsg = strtolower(trim($conversationHistory[$i]['content']));

                // Skip bare keywords (affirmative/continuation responses)
                $isBareKeyword = in_array($userMsg, $continuationKeywords) ||
                    preg_match('/^(yes|ok|ha+n?|sure|continue|details|more|hmm+|acch+a|theek|thik|thanks?|thanku?|thnx|wow|nice|great|good|cool|awesome)[\s\.\!\?]*$/i', $userMsg);

                // Skip greetings
                $isGreetingMsg = preg_match('/^(hi+|hey+|hello+|namaste|namaskar)[\s\.\!\?]*$/i', $userMsg);

                \Log::info('CONTINUATION DEBUG - Checking user msg', [
                    'index' => $i,
                    'msg' => $userMsg,
                    'is_keyword' => $isBareKeyword,
                    'is_greeting' => $isGreetingMsg,
                    'strlen' => strlen($userMsg),
                ]);

                // =============================================
                // FIX: Use strlen >= 3 (NOT strlen > 15!)
                // "Human eye" = 9 chars, "Body Chest" = 10 chars
                // These ARE real questions and must NOT be skipped!
                // =============================================
                if (!$isBareKeyword && !$isGreetingMsg && strlen($userMsg) >= 3) {
                    $lastUserQuestion = $conversationHistory[$i]['content'];
                    \Log::info('CONTINUATION DEBUG - FOUND QUESTION', [
                        'index' => $i,
                        'question' => $lastUserQuestion,
                    ]);
                    break;
                }
            }
        }

        if ($lastAssistantMessage && $lastUserQuestion) {
            $topicPreview = strtok($lastAssistantMessage, "\n");
            $topicPreview = substr($topicPreview, 0, 150);

            $result = "[CONTINUATION REQUEST] User wants more details.\n" .
                       "LAST USER QUESTION WAS: \"{$lastUserQuestion}\"\n" .
                       "YOUR LAST RESPONSE STARTED WITH: \"{$topicPreview}\"\n" .
                       "IMPORTANT: Provide a detailed explanation about \"{$lastUserQuestion}\". " .
                       "Expand on your previous short answer. DO NOT switch to any other topic.";

            \Log::info('Continuation detected - UNIFIED FIX', [
                'original_message' => $contentLower,
                'last_user_question' => substr($lastUserQuestion, 0, 50),
                'last_topic_preview' => substr($topicPreview, 0, 50),
            ]);

            return $result;

        } elseif ($lastAssistantMessage) {
            $topicPreview = strtok($lastAssistantMessage, "\n");
            $topicPreview = substr($topicPreview, 0, 100);

            $result = "[CONTINUATION REQUEST] User said: \"{$content}\"\n" .
                       "IMPORTANT: Continue explaining YOUR LAST RESPONSE which was about: \"{$topicPreview}\"\n" .
                       "DO NOT explain any other topic. ONLY expand on your immediately previous response.";

            \Log::info('Continuation detected - fallback (unified)', [
                'original_message' => $contentLower,
                'last_topic_preview' => $topicPreview,
            ]);

            return $result;
        }

        return null;
    }

    /**
     * Send message with STREAMING response (Server-Sent Events)
     *
     * BUG FIX #1 & #3: Now uses shared buildContinuationContext() helper
     * and same history limit (6) as sendMessage()
     */
    public function sendMessageStream(Request $request, $chatId)
    {
        $request->validate([
            'content' => 'nullable|string',
            'ai_model_id' => 'nullable|integer',
            'image' => 'nullable|string',
        ]);

        $user = auth()->user();
        $content = $request->input('content', '');
        $modelId = $request->input('ai_model_id');
        $imageBase64 = $request->input('image');

        // Verify chat exists and belongs to user
        $chat = \App\Models\MobileChat::where('id', $chatId)
            ->where('user_id', $user->id)
            ->first();

        if (!$chat) {
            return response()->json(['error' => 'Chat not found'], 404);
        }

        // Rate limiting
        $rateLimitKey = 'chat_message:' . $user->id;
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($rateLimitKey, 10)) {
            $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn($rateLimitKey);
            return response()->json(['error' => "Rate limit exceeded. Wait {$seconds}s"], 429);
        }
        \Illuminate\Support\Facades\RateLimiter::hit($rateLimitKey, 60);

        // Require either content or image
        if (empty($content) && empty($imageBase64)) {
            return response()->json(['error' => 'Please provide a message or image'], 400);
        }

        // Prepare image data if provided
        $imageData = null;
        if ($imageBase64) {
            $imageData = [
                'uri' => 'data:image/jpeg;base64,' . $imageBase64,
                'type' => 'image/jpeg',
            ];
        }

        // Save user message
        $userContent = empty($content) && $imageBase64 ? '[Image uploaded]' : $content;
        $userMessageRecord = \App\Models\MobileChatMessage::create([
            'mobile_chat_id' => $chatId,
            'sender' => 'user',
            'content' => $userContent,
            'image' => $imageData,
        ]);

        // Record user activity for smart cache conversation tracking
        try {
            app(ConversationGuard::class)->recordActivity($chatId, 'user', $userContent);
        } catch (\Exception $e) {
            // Silent fail - cache tracking is non-critical
        }

        // =============================================
        // BUG FIX #3: Increased limit for better context
        // INCREASED: 6 -> 10 to prevent topic mixing
        // =============================================
        $conversationHistory = \App\Models\MobileChatMessage::where('mobile_chat_id', $chatId)
            ->where('id', '!=', $userMessageRecord->id)
            ->select(['sender', 'content'])
            ->orderBy('id', 'desc')
            ->limit(10)  // 10 messages = 5 exchanges for better context
            ->get()
            ->reverse()
            ->values()  // CRITICAL: Reset array keys after reverse!
            ->map(fn($msg) => [
                'role' => $msg->sender === 'user' ? 'user' : 'assistant',
                'content' => $msg->sender === 'user'
                    ? substr($msg->content, 0, 300)
                    : substr($msg->content, 0, 800)
            ])
            ->toArray();

        // =============================================
        // BUG FIX #1: Use SHARED continuation helper
        // Was using strlen > 15 which skipped short questions
        // like "Human eye" (9 chars) causing wrong topic!
        // =============================================
        $continuationKeywords = config('smartcache.continuation_keywords', ['yes', 'continue', 'haan', 'ha', 'ok', 'sure', 'go ahead', 'explain', 'tell me more', 'details', 'aur batao', 'aage']);
        $contentLower = strtolower(trim($content));
        $isContinuationRequest = in_array($contentLower, $continuationKeywords) || strlen($content) <= 15 && preg_match('/^(yes|ok|ha+n?|sure|continue|details|explain|more)\b/i', $content);

        if ($isContinuationRequest && count($conversationHistory) > 0) {
            $result = $this->buildContinuationContext($conversationHistory, $continuationKeywords, $content, $contentLower);
            if ($result) {
                $content = $result;
            }
        }

        // Return streaming response
        return response()->stream(function () use ($content, $modelId, $conversationHistory, $imageData, $chatId, $user) {
            if (ob_get_level()) ob_end_clean();

            header('Content-Type: text/event-stream');
            header('Cache-Control: no-cache');
            header('Connection: keep-alive');
            header('X-Accel-Buffering: no');

            $fullResponse = '';
            $streamStartedAt = microtime(true);
            $firstTokenSent = false;

            try {
                $streamCallback = function ($chunk) use (&$fullResponse, &$firstTokenSent, $streamStartedAt) {
                    $fullResponse .= $chunk;

                    if (!$firstTokenSent) {
                        $ttftMs = round((microtime(true) - $streamStartedAt) * 1000);
                        echo "data: " . json_encode(['meta' => ['ttft_ms' => $ttftMs]]) . "\n\n";
                        $firstTokenSent = true;
                    }

                    echo "data: " . json_encode(['content' => $chunk]) . "\n\n";
                    flush();
                };

                $aiResult = $this->aiService->chat(
                    $content,
                    $modelId,
                    $conversationHistory,
                    $streamCallback,
                    $imageData,
                    'chat',
                    $user->id
                );

                if (!$aiResult['success']) {
                    echo "data: " . json_encode(['error' => $aiResult['error'] ?? 'AI service error']) . "\n\n";
                    flush();
                    echo "data: [DONE]\n\n";
                    flush();
                    return;
                }

                $finalResponse = !empty($fullResponse) ? $fullResponse : ($aiResult['content'] ?? '');

                $aiMessageRecord = \App\Models\MobileChatMessage::create([
                    'mobile_chat_id' => $chatId,
                    'sender' => 'assistant',
                    'content' => $finalResponse,
                ]);

                // Record AI activity for smart cache conversation tracking
                try {
                    app(ConversationGuard::class)->recordActivity($chatId, 'assistant', $finalResponse);
                } catch (\Exception $e) {
                    // Silent fail - cache tracking is non-critical
                }

                $usageLimitService = app(\App\Services\UsageLimitService::class);
                $usageLimitService->recordUsage($user, 'ai_doubt');

                \App\Models\MobileChat::where('id', $chatId)->update([
                    'last_message_at' => now(),
                    'ai_model_id' => $modelId,
                ]);

                $totalMs = round((microtime(true) - $streamStartedAt) * 1000);
                echo "data: " . json_encode([
                    'done' => true,
                    'meta' => [
                        'duration_ms' => $totalMs,
                        'provider' => $aiResult['provider'] ?? 'openai',
                        'model' => $aiResult['model'] ?? null,
                    ],
                ]) . "\n\n";
                echo "data: [DONE]\n\n";
                flush();

            } catch (\Exception $e) {
                \Log::error('Streaming error', [
                    'user_id' => $user->id,
                    'chat_id' => $chatId,
                    'error' => $e->getMessage(),
                ]);

                echo "data: " . json_encode(['error' => $e->getMessage()]) . "\n\n";
                echo "data: [DONE]\n\n";
                flush();
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Save user feedback for AI response optimization
     */
    public function saveFeedback(Request $request)
    {
        $request->validate([
            'message_id' => 'required',
            'chat_id' => 'required',
            'feedback_type' => 'required|in:like,dislike',
            'message_content' => 'nullable|string',
            'question' => 'nullable|string',
            'feedback_reason' => 'nullable|string|max:100',
            'user_comment' => 'nullable|string|max:500',
        ]);

        $user = $request->user();
        $feedbackType = $request->input('feedback_type');
        $chatId = $request->input('chat_id');
        $messageId = $request->input('message_id');
        $aiResponse = $request->input('message_content', '');
        $feedbackReason = $request->input('feedback_reason');
        if ($feedbackReason) {
            $feedbackReason = str_replace('_', ' ', $feedbackReason);
        }

        $question = $request->input('question', '');
        if (empty($question)) {
            $prevUser = \App\Models\MobileChatMessage::where('mobile_chat_id', $chatId)
                ->where('sender', 'user')
                ->when(is_numeric($messageId), fn ($q) => $q->where('id', '<', (int) $messageId))
                ->orderByDesc('id')
                ->first();
            $question = $prevUser?->content ?? '';
        }

        if (empty($aiResponse)) {
            $aiMessage = \App\Models\MobileChatMessage::find($messageId);
            $aiResponse = $aiMessage?->content ?? '';
        }

        try {
            $result = \App\Services\FeedbackAnalysisService::processFeedback([
                'user_id' => $user->id,
                'conversation_id' => (string) $chatId,
                'question' => $question,
                'ai_response' => $aiResponse,
                'feedback_type' => $feedbackType,
                'feedback_reason' => $feedbackReason,
                'user_comment' => $request->input('user_comment'),
                'was_helpful' => $feedbackType === 'like',
                'response_rating' => $feedbackType === 'like' ? 5 : 2,
            ]);

            try {
                \DB::table('ai_feedback')->insert([
                    'user_id' => $user->id,
                    'chat_id' => $chatId,
                    'message_id' => (string) $messageId,
                    'feedback_type' => $feedbackType,
                    'message_content' => $aiResponse,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (\Exception $legacyError) {
                \Log::warning('Legacy ai_feedback insert skipped', [
                    'error' => $legacyError->getMessage(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Feedback saved successfully',
                'feedback_id' => $result['feedback_id'] ?? null,
                'follow_up' => $result['follow_up'] ?? null,
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to save feedback', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Could not save feedback. Please try again.',
            ], 500);
        }
    }

    /**
     * Auto-detect language from message content
     */
    private function detectLanguageFromMessage(string $message): string
    {
        if (empty($message)) {
            return 'hinglish';
        }

        // Check if user explicitly requested Hindi in message
        $msgLower = strtolower($message);
        if (strpos($msgLower, 'in hindi') !== false || strpos($msgLower, 'hindi me') !== false || strpos($msgLower, 'hindi mein') !== false || strpos($msgLower, 'hindi mai') !== false) {
            return 'hindi';
        }

        if (preg_match('/[\x{0900}-\x{097F}]/u', $message)) {
            return 'hindi';
        }

        $hinglishWords = [
            'kya', 'hai', 'kaise', 'karo', 'samjhao', 'bata', 'batao', 'nahi', 'haan',
            'accha', 'theek', 'kar', 'mein', 'yeh', 'woh', 'aur', 'bhi', 'toh', 'na',
            'padhao', 'sikho', 'dekho', 'sunao', 'likho', 'padho', 'jaao', 'aao',
            'kyu', 'kaun', 'kab', 'kaha', 'kitna', 'konsa', 'hota', 'hoti', 'hote',
        ];

        $messageLower = strtolower($message);
        $hinglishCount = 0;

        foreach ($hinglishWords as $word) {
            if (preg_match('/\b' . preg_quote($word, '/') . '\b/', $messageLower)) {
                $hinglishCount++;
            }
        }

        if ($hinglishCount >= 2) {
            return 'hinglish';
        }

        return 'english';
    }

    /**
     * Detect language from conversation history
     * Used when current message is a short continuation like "yes", "haan"
     */
    private function detectLanguageFromHistory(array $history): string
    {
        // Look for Hindi indicators in user messages from history
        foreach ($history as $msg) {
            if (isset($msg['role']) && $msg['role'] === 'user' && isset($msg['content'])) {
                $content = strtolower($msg['content']);
                // Check for "hindi" word anywhere (e.g., "explain photosynthesis hindi")
                if (preg_match('/\bhindi\b/i', $content)) {
                    return 'hindi';
                }
                // Check for Devanagari script
                if (preg_match('/[\x{0900}-\x{097F}]/u', $msg['content'])) {
                    return 'hindi';
                }
                // Check for hinglish patterns
                $hinglishWords = ['kya', 'hai', 'kaise', 'samjhao', 'batao', 'bata', 'padhao', 'sikho'];
                foreach ($hinglishWords as $word) {
                    if (preg_match('/\b' . $word . '\b/i', $content)) {
                        return 'hinglish';
                    }
                }
            }
            // Also check assistant's previous response for Hindi content
            if (isset($msg['role']) && $msg['role'] === 'assistant' && isset($msg['content'])) {
                // If previous response was in Hindi (contains Hinglish words), continue in Hindi
                if (preg_match('/\b(hai|hain|karte|karta|hota|milta|banate|aur)\b/i', $msg['content'])) {
                    return 'hindi';
                }
            }
        }
        return 'english';
    }
}