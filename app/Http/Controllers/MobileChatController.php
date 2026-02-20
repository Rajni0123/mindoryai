<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use App\Services\UnifiedAIService;
use App\Services\FileStorageService;
use App\Services\Cache\SmartCacheService;
use App\Services\UsageLimitService;
use App\Services\LearningAnalyticsService;
use App\Models\FrontendConfig;

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

        // Auto-detect language from message content (priority over request param)
        $language = $this->detectLanguageFromMessage($content);

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

                    // Set high memory limit and timeout for PDF processing
                    // Don't store original - we'll keep the high limit permanently
                    ini_set('memory_limit', '512M');
                    ini_set('max_execution_time', '60');

                    $parser = new \Smalot\PdfParser\Parser();

                    // Parse with config to reduce memory usage
                    $config = new \Smalot\PdfParser\Config();
                    $config->setRetainImageContent(false); // Don't extract images
                    $parser = new \Smalot\PdfParser\Parser([], $config);

                    $pdf = $parser->parseFile($file->getRealPath());

                    // Get only first 20 pages to limit memory usage
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

                    // Don't restore limits - keep high limits for remainder of request
                    // (Restoring fails if memory usage is already higher than original limit)

                    // Clean up extracted text
                    $extractedText = preg_replace('/\s+/', ' ', $extractedText);
                    $extractedText = trim($extractedText);

                    if (!empty($extractedText)) {
                        // Limit text length
                        $maxChars = 8000;
                        if (strlen($extractedText) > $maxChars) {
                            $extractedText = substr($extractedText, 0, $maxChars) . '...';
                        }

                        // Append extracted text to content
                        $pdfPrefix = "📄 PDF Content:\n\n";
                        $content = empty($content)
                            ? $pdfPrefix . $extractedText
                            : $content . "\n\n" . $pdfPrefix . $extractedText;

                        // Clear image data since we're using text instead
                        $imageData = null;
                        $hasImage = false;

                        \Log::info('PDF text extracted successfully', [
                            'text_length' => strlen($extractedText),
                            'preview' => substr($extractedText, 0, 100)
                        ]);
                    } else {
                        \Log::warning('PDF text extraction returned empty - will use Gemini for PDF processing');
                        // PDF text extraction failed/empty - We MUST use Gemini for PDF URLs
                        // OpenAI cannot process PDF URLs, only Gemini supports PDFs
                    }

                } catch (\Exception $e) {
                    \Log::error('PDF text extraction failed', [
                        'error' => $e->getMessage(),
                        'file_size_mb' => round($file->getSize() / 1024 / 1024, 2),
                        'error_class' => get_class($e),
                    ]);

                    // Restore limits if error occurred
                    if (isset($originalMemoryLimit)) {
                        ini_set('memory_limit', $originalMemoryLimit);
                    }
                    if (isset($originalTimeLimit)) {
                        ini_set('max_execution_time', $originalTimeLimit);
                    }

                    // Check if it's a memory error
                    $errorMsg = strtolower($e->getMessage());
                    if (strpos($errorMsg, 'memory') !== false ||
                        strpos($errorMsg, 'exhausted') !== false ||
                        strpos($errorMsg, 'allocation') !== false) {

                        // Return user-friendly error for memory issues
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

                    // For other errors, return error message
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

        // =============================================
        // AUTOMATIC MODEL SELECTION FOR PDF FILES
        // =============================================
        // If we have a PDF file with URL (not text-extracted), we MUST use Gemini
        // OpenAI Vision API does NOT support PDF files - only images
        if ($imageData && isset($imageData['type']) && $imageData['type'] === 'application/pdf') {
            \Log::info('PDF detected with URL - automatically switching to Gemini');

            // Find active Gemini model
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
                // No Gemini model available - return error
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
        // Protect against abuse - 10 messages per minute
        $rateLimitKey = 'chat_message:' . $user->id;
        $maxAttempts = 10; // 10 messages per minute
        $decayMinutes = 1;

        if (RateLimiter::tooManyAttempts($rateLimitKey, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            // Return error in array format for consistency
            $errorMessage = [
                'id' => time(),
                'chat_id' => $chatId,
                'sender' => 'system',
                'content' => "⚠️ Too many messages. Please wait {$seconds} seconds before sending another message.",
                'error' => 'rate_limit_exceeded',
                'retry_after' => $seconds,
                'created_at' => now()->toISOString(),
            ];

            // Return 200 so mobile app doesn't throw error, but include error in message
            return response()->json([$errorMessage], 200);
        }

        // =============================================
        // STEP 2: INCREMENT RATE LIMITER
        // =============================================
        RateLimiter::hit($rateLimitKey, $decayMinutes * 60);

        // =============================================
        // STEP 4: CREATE USER MESSAGE
        // =============================================

        // If user uploaded image without text, set placeholder content
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
            // Store file metadata (URL, not Base64)
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

        // =============================================
        // STEP 5: GET AI RESPONSE
        // =============================================
        try {
            // Get conversation history for context - need enough for proper continuity
            $conversationHistory = \App\Models\MobileChatMessage::where('mobile_chat_id', $chatId)
                ->where('id', '!=', $userMessageRecord->id)
                ->select(['sender', 'content'])  // Only fetch needed columns
                ->orderBy('id', 'desc')  // Use id for faster query
                ->limit(6)  // Need 6 for proper context (3 exchanges)
                ->get()
                ->reverse()
                ->map(fn($msg) => [
                    'role' => $msg->sender === 'user' ? 'user' : 'assistant',
                    // Keep more context for assistant responses to maintain topic continuity
                    'content' => $msg->sender === 'user'
                        ? substr($msg->content, 0, 300)  // User messages can be shorter
                        : substr($msg->content, 0, 800)  // AI responses need more context for topic
                ])
                ->toArray();

            // =============================================
            // NEW TOPIC / GREETING DETECTION
            // =============================================
            // Detect if user is starting a new topic/greeting - clear history for fresh start
            // Use unified config instead of hardcoded arrays (Issue #2 fix - duplicate logic)
            $greetingKeywords = config('smartcache.greeting_keywords', ['hello', 'hi', 'hey', 'namaste', 'namaskar', 'good morning', 'good afternoon', 'good evening', 'good night', 'hii', 'hiii', 'hiiii', 'helloo', 'hellooo']);
            $newTopicKeywords = ['new question', 'nayi question', 'naya sawal', 'different topic', 'change topic', 'kuch aur', 'something else', 'new topic'];

            // Use original user content for greeting detection (not modified content)
            $originalContent = $request->input('content', '');
            $contentLower = strtolower(trim($originalContent));

            // DEBUG LOG - ALWAYS LOG
            \Log::info('GREETING CHECK DEBUG', [
                'original_content' => $originalContent,
                'content_lower' => $contentLower,
                'content_var' => $content,
                'history_count_before' => count($conversationHistory),
            ]);

            // Check if it's a greeting or new topic request
            $isGreeting = in_array($contentLower, $greetingKeywords) || preg_match('/^(hi+|hey+|hello+|namaste|namaskar)\b/i', $originalContent);
            $isNewTopic = false;
            foreach ($newTopicKeywords as $keyword) {
                if (stripos($contentLower, $keyword) !== false) {
                    $isNewTopic = true;
                    break;
                }
            }

            // Clear conversation history for greetings/new topics to start fresh
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
            // CONTINUATION CONTEXT FIX
            // =============================================
            // Detect if user wants continuation (Yes, Continue, etc.)
            // Use unified config instead of hardcoded arrays (Issue #2 fix - duplicate logic)
            $continuationKeywords = config('smartcache.continuation_keywords', ['yes', 'continue', 'haan', 'ha', 'ok', 'sure', 'go ahead', 'explain', 'tell me more', 'details', 'aur batao', 'aage']);
            $isContinuationRequest = !$isGreeting && !$isNewTopic && (in_array($contentLower, $continuationKeywords) || strlen($content) <= 15 && preg_match('/^(yes|ok|ha+n?|sure|continue|details|explain|more)\b/i', $content));

            // If it's a continuation request, find the last REAL question and AI response
            if ($isContinuationRequest && count($conversationHistory) > 0) {
                // Find the last REAL user question (not a continuation keyword)
                $lastUserQuestion = null;
                $lastAssistantMessage = null;

                // First, find the last assistant message
                for ($i = count($conversationHistory) - 1; $i >= 0; $i--) {
                    if ($conversationHistory[$i]['role'] === 'assistant') {
                        $lastAssistantMessage = $conversationHistory[$i]['content'];
                        break;
                    }
                }

                // Then, find the last REAL user question (skip continuation keywords)
                for ($i = count($conversationHistory) - 1; $i >= 0; $i--) {
                    if ($conversationHistory[$i]['role'] === 'user') {
                        $userMsg = strtolower(trim($conversationHistory[$i]['content']));
                        // Skip if it's a continuation keyword itself (ONLY if entire message is the keyword)
                        // Fix: "explain photosynthesis" should NOT be skipped, only bare "explain" should
                        $isBareKeyword = in_array($userMsg, $continuationKeywords) ||
                            preg_match('/^(yes|ok|ha+n?|sure|continue|details|more|hmm+|acch+a|theek|thik)[\s\.\!\?]*$/i', $userMsg);

                        // Real question: not a bare keyword AND has meaningful content (>15 chars)
                        if (!$isBareKeyword && strlen($userMsg) > 15) {
                            $lastUserQuestion = $conversationHistory[$i]['content'];
                            break;
                        }
                    }
                }

                if ($lastAssistantMessage && $lastUserQuestion) {
                    // Extract the topic from the last AI response (first 100 chars or first line)
                    $topicPreview = strtok($lastAssistantMessage, "\n");
                    $topicPreview = substr($topicPreview, 0, 150);

                    // Use BOTH last question AND last response for context
                    $content = "[CONTINUATION REQUEST] User wants more details.\n" .
                               "LAST USER QUESTION WAS: \"{$lastUserQuestion}\"\n" .
                               "YOUR LAST RESPONSE STARTED WITH: \"{$topicPreview}\"\n" .
                               "IMPORTANT: Provide a detailed explanation about \"{$lastUserQuestion}\". " .
                               "Expand on your previous short answer. DO NOT switch to any other topic.";

                    \Log::info('Continuation detected - FIXED', [
                        'original_message' => $contentLower,
                        'last_user_question' => substr($lastUserQuestion, 0, 50),
                        'last_topic_preview' => substr($topicPreview, 0, 50),
                    ]);
                } elseif ($lastAssistantMessage) {
                    // Fallback to old behavior if no real question found
                    $topicPreview = strtok($lastAssistantMessage, "\n");
                    $topicPreview = substr($topicPreview, 0, 100);

                    $content = "[CONTINUATION REQUEST] User said: \"{$content}\"\n" .
                               "IMPORTANT: Continue explaining YOUR LAST RESPONSE which was about: \"{$topicPreview}\"\n" .
                               "DO NOT explain any other topic. ONLY expand on your immediately previous response.";

                    \Log::info('Continuation detected - fallback', [
                        'original_message' => $contentLower,
                        'last_topic_preview' => $topicPreview,
                    ]);
                }
            }

            // Log the request for debugging
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
            // Only check cache for text-only messages (no images/PDFs)
            // Greetings and follow-ups are automatically filtered by SmartCacheService
            $cacheHit = null;

            // AGGRESSIVE SAFETY CHECK: Block short messages and continuation keywords from cache
            // This prevents the "Yes -> Photosynthesis" bug completely
            $shortMessageBlocked = mb_strlen(trim($originalContent)) <= 20;

            // Use config-based continuation keywords for consistency
            $continuationKeywords = config('smartcache.continuation_keywords', [
                'yes', 'yeah', 'yep', 'yup', 'ya', 'y', 'ok', 'okay', 'okk',
                'sure', 'right', 'correct', 'continue', 'next', 'more',
                'details', 'explain', 'hmm', 'hmmm', 'accha', 'acha', 'achha',
                'sahi', 'theek', 'thik', 'haan', 'ha', 'ji', 'haanji',
                'thanks', 'thank', 'thanku', 'thnx', 'wow', 'nice', 'great', 'good', 'cool'
            ]);
            $continuationPattern = '/^(' . implode('|', array_map(function($kw) {
                return preg_quote($kw, '/');
            }, $continuationKeywords)) . ')\b/i';
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
                    $originalContent,  // Use original, unmodified content
                    'ai_doubt',        // Source type for chat
                    null,              // Subject (could be detected from content)
                    null,              // Exam type
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

                // Save user message first
                $userMessageRecord = \App\Models\MobileChatMessage::create([
                    'mobile_chat_id' => $chatId,
                    'sender' => 'user',
                    'content' => $request->input('content'),
                    'file_url' => $fileUrl,
                    'file_type' => $hasImage ? 'image' : null,
                ]);

                $userMessage = [
                    'id' => $userMessageRecord->id,
                    'chat_id' => $chatId,
                    'sender' => 'user',
                    'content' => $request->input('content'),
                    'created_at' => $userMessageRecord->created_at->toIso8601String(),
                ];

                // Save AI response
                $aiMessageRecord = \App\Models\MobileChatMessage::create([
                    'mobile_chat_id' => $chatId,
                    'sender' => 'assistant',
                    'content' => $aiResponse,
                ]);

                $aiMessage = [
                    'id' => $aiMessageRecord->id,
                    'chat_id' => $chatId,
                    'sender' => 'assistant',
                    'content' => $aiResponse,
                    'cached' => true,  // Mark as cached response
                    'created_at' => $aiMessageRecord->created_at->toIso8601String(),
                ];

                // Update chat
                $chat->update(['last_message_at' => now()]);
                if ($chat->title === 'New Chat' && !empty($content)) {
                    $chat->update(['title' => \App\Models\MobileChat::generateTitleFromMessage($content)]);
                }

                // Return cached response (no credit deduction for cached responses)
                return response()->json([$userMessage, $aiMessage]);
            }

            // Determine feature based on content/image type
            $feature = 'chat'; // Default
            if ($imageData) {
                // Check if it's a PDF or quiz generation
                if (isset($imageData['type']) && $imageData['type'] === 'application/pdf') {
                    $feature = 'pdf_solve';
                } elseif (stripos($content, 'generate') !== false && stripos($content, 'question') !== false) {
                    $feature = 'mcq_generation';
                } else {
                    $feature = 'pdf_solve'; // Image analysis
                }
            }

            // DEBUG: Log before AI call
            \Log::info('AI CALL DEBUG', [
                'user_message' => $content,
                'original_content' => $originalContent ?? $content,
                'chat_id' => $chatId,
                'user_id' => $user->id,
                'history_count' => count($conversationHistory),
                'history' => array_slice($conversationHistory, -5), // Last 5 messages
                'language' => $language,
                'feature' => $feature,
                'cache_hit' => isset($cacheHit) && $cacheHit ? 'YES' : 'NO',
            ]);

            // Use UnifiedAIService to get response with optimized settings
            $aiResult = $this->aiService->chat(
                $content,
                $modelId,  // Use the selected model ID from request
                $conversationHistory,
                null,  // No streaming for now
                $imageData,  // Pass image data for vision analysis
                $feature,    // Feature for optimization
                $user->id,   // User ID for tracking
                $language    // User's preferred language
            );

            \Log::info('AI Service Response', [
                'user_id' => $user->id,
                'chat_id' => $chatId,
                'success' => $aiResult['success'] ?? false,
                'error' => $aiResult['error'] ?? null,
                'has_content' => isset($aiResult['content']),
            ]);

            if (!$aiResult['success']) {
                throw new \Exception($aiResult['error'] ?? 'AI service error');
            }

            $aiResponse = $aiResult['content'];

            // =============================================
            // SMART CACHE STORE (After Successful AI Response)
            // =============================================
            // Store response in cache for future lookups
            // Only store if: no image, not continuation, cache is enabled
            if (!$hasImage && !$isContinuationRequest && config('smartcache.enabled', true)) {
                try {
                    $stored = $this->cacheService->store(
                        $originalContent,      // Original question
                        $aiResponse,           // AI's answer
                        'ai_doubt',            // Source type
                        null,                  // Subject (could be detected)
                        null,                  // Topic
                        null,                  // Class level
                        null,                  // Exam type
                        $aiResult['tokens_used'] ?? 0  // Token count
                    );

                    if ($stored) {
                        \Log::info('[SmartCache] STORED new response', [
                            'question' => substr($originalContent, 0, 50),
                        ]);
                    }
                } catch (\Exception $cacheError) {
                    // Don't fail the request if caching fails
                    \Log::warning('[SmartCache] Store failed', [
                        'error' => $cacheError->getMessage(),
                    ]);
                }
            }

        } catch (\Exception $e) {
            // If AI fails, return error but don't deduct credits
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

        // Save AI message to database
        $aiMessageRecord = \App\Models\MobileChatMessage::create([
            'mobile_chat_id' => $chatId,
            'sender' => 'assistant',
            'content' => $aiResponse,
        ]);

        // Record usage for successful message (fixes sidebar counter not updating)
        $this->usageLimitService->recordUsage($user, 'ai_doubt');

        $aiMessage = [
            'id' => $aiMessageRecord->id,
            'chat_id' => $chatId,
            'sender' => 'assistant',
            'content' => $aiResponse,
            'created_at' => $aiMessageRecord->created_at->toIso8601String(),
        ];

        // Update chat's last_message_at
        $chat->update([
            'last_message_at' => now(),
            'ai_model_id' => $modelId,
        ]);

        // Generate and update chat title from first message (if still "New Chat")
        if ($chat->title === 'New Chat' && !empty($content)) {
            $generatedTitle = \App\Models\MobileChat::generateTitleFromMessage($content);
            $chat->update(['title' => $generatedTitle]);
        }

        // =============================================
        // STEP 8: RETURN RESPONSE (BACKWARD COMPATIBLE)
        // =============================================
        // Log what we're about to return for debugging
        \Log::info('Returning messages to client', [
            'user_id' => $user->id,
            'chat_id' => $chatId,
            'message_count' => 2,
            'user_sender' => $userMessage['sender'],
            'ai_sender' => $aiMessage['sender'],
            'ai_content_length' => strlen($aiMessage['content']),
        ]);

        // Return array of messages directly (maintains compatibility)
        return response()->json([$userMessage, $aiMessage]);
    }

    /**
     * Get chat messages - OPTIMIZED
     */
    public function getMessages($chatId)
    {
        $user = auth()->user();

        // Verify chat belongs to user - use exists() for faster check
        $chatExists = \App\Models\MobileChat::where('id', $chatId)
            ->where('user_id', $user->id)
            ->exists();

        if (!$chatExists) {
            return response()->json(['error' => 'Chat not found'], 404);
        }

        // Get messages directly with optimized query - select only needed columns
        $messages = \App\Models\MobileChatMessage::where('mobile_chat_id', $chatId)
            ->select(['id', 'mobile_chat_id', 'sender', 'content', 'image', 'created_at'])
            ->orderBy('id', 'asc')  // Use id for faster ordering
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

        // Get user's chats with optimized query - select only needed columns
        $chats = \App\Models\MobileChat::where('user_id', $user->id)
            ->select(['id', 'title', 'created_at', 'updated_at', 'last_message_at'])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')  // Use id instead of created_at for faster fallback
            ->limit(50)  // Limit to recent chats for faster response
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

        // Create new chat in database
        $chat = \App\Models\MobileChat::create([
            'user_id' => $user->id,
            'title' => $request->input('title', 'New Chat'),
            'ai_model_id' => null, // Will be set when first message is sent
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

        // Verify chat belongs to user
        $chat = \App\Models\MobileChat::where('id', $chatId)
            ->where('user_id', $user->id)
            ->first();

        if (!$chat) {
            return response()->json(['error' => 'Chat not found'], 404);
        }

        // Delete chat (messages will be cascaded)
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

        // Verify chat belongs to user
        $chat = \App\Models\MobileChat::where('id', $chatId)
            ->where('user_id', $user->id)
            ->first();

        if (!$chat) {
            return response()->json(['error' => 'Chat not found'], 404);
        }

        // Update title
        $chat->update(['title' => $request->input('title')]);

        return response()->json([
            'id' => $chatId,
            'title' => $chat->title,
            'updated_at' => $chat->updated_at->toIso8601String(),
        ]);
    }

    /**
     * Send message with STREAMING response (Server-Sent Events)
     *
     * This endpoint streams the AI response in real-time for better UX.
     * - Time to first word: 0.3-0.5s instead of 3-5s
     * - Perceived speed: 10x faster!
     *
     * @param Request $request
     * @param int $chatId
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function sendMessageStream(Request $request, $chatId)
    {
        $request->validate([
            'content' => 'nullable|string',
            'ai_model_id' => 'nullable|integer',
            'image' => 'nullable|string', // Base64 image
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

        // Get conversation history (optimized)
        $conversationHistory = \App\Models\MobileChatMessage::where('mobile_chat_id', $chatId)
            ->where('id', '!=', $userMessageRecord->id)
            ->select(['sender', 'content'])
            ->orderBy('id', 'desc')
            ->limit(4)
            ->get()
            ->reverse()
            ->map(fn($msg) => [
                'role' => $msg->sender === 'user' ? 'user' : 'assistant',
                'content' => substr($msg->content, 0, 500)
            ])
            ->toArray();

        // Continuation context fix for streaming
        // Use unified config instead of hardcoded arrays (Issue #2 fix - duplicate logic)
        $continuationKeywords = config('smartcache.continuation_keywords', ['yes', 'continue', 'haan', 'ha', 'ok', 'sure', 'go ahead', 'explain', 'tell me more', 'details', 'aur batao', 'aage']);
        $contentLower = strtolower(trim($content));
        $isContinuationRequest = in_array($contentLower, $continuationKeywords) || strlen($content) <= 15 && preg_match('/^(yes|ok|ha+n?|sure|continue|details|explain|more)\b/i', $content);

        if ($isContinuationRequest && count($conversationHistory) > 0) {
            // Find the last REAL user question (not a continuation keyword)
            $lastUserQuestion = null;
            $lastAssistantMessage = null;

            // First, find the last assistant message
            for ($i = count($conversationHistory) - 1; $i >= 0; $i--) {
                if ($conversationHistory[$i]['role'] === 'assistant') {
                    $lastAssistantMessage = $conversationHistory[$i]['content'];
                    break;
                }
            }

            // Then, find the last REAL user question (skip continuation keywords)
            for ($i = count($conversationHistory) - 1; $i >= 0; $i--) {
                if ($conversationHistory[$i]['role'] === 'user') {
                    $userMsg = strtolower(trim($conversationHistory[$i]['content']));
                    // Skip if it's a continuation keyword itself
                    if (!in_array($userMsg, $continuationKeywords) &&
                        strlen($userMsg) > 15 &&
                        !preg_match('/^(yes|ok|ha+n?|sure|continue|details|explain|more|hmm+|acch+a|theek|thik)\b/i', $userMsg)) {
                        $lastUserQuestion = $conversationHistory[$i]['content'];
                        break;
                    }
                }
            }

            if ($lastAssistantMessage && $lastUserQuestion) {
                $topicPreview = strtok($lastAssistantMessage, "\n");
                $topicPreview = substr($topicPreview, 0, 150);

                // Use BOTH last question AND last response for context
                $content = "[CONTINUATION REQUEST] User wants more details.\n" .
                           "LAST USER QUESTION WAS: \"{$lastUserQuestion}\"\n" .
                           "YOUR LAST RESPONSE STARTED WITH: \"{$topicPreview}\"\n" .
                           "IMPORTANT: Provide a detailed explanation about \"{$lastUserQuestion}\". " .
                           "Expand on your previous short answer. DO NOT switch to any other topic.";
            } elseif ($lastAssistantMessage) {
                // Fallback to old behavior if no real question found
                $topicPreview = strtok($lastAssistantMessage, "\n");
                $topicPreview = substr($topicPreview, 0, 100);
                $content = "[CONTINUATION REQUEST] User said: \"{$content}\"\n" .
                           "IMPORTANT: Continue explaining YOUR LAST RESPONSE which was about: \"{$topicPreview}\"\n" .
                           "DO NOT explain any other topic. ONLY expand on your immediately previous response.";
            }
        }

        // Return streaming response
        return response()->stream(function () use ($content, $modelId, $conversationHistory, $imageData, $chatId, $user) {
            // Disable output buffering for real-time streaming
            if (ob_get_level()) ob_end_clean();

            // Send headers for SSE
            header('Content-Type: text/event-stream');
            header('Cache-Control: no-cache');
            header('Connection: keep-alive');
            header('X-Accel-Buffering: no'); // Disable nginx buffering

            $fullResponse = '';

            try {
                // Use UnifiedAIService with streaming callback
                $streamCallback = function ($chunk) use (&$fullResponse) {
                    $fullResponse .= $chunk;

                    // Send chunk to client
                    echo "data: " . json_encode(['content' => $chunk]) . "\n\n";
                    flush();
                };

                // Call AI service with streaming
                $aiResult = $this->aiService->chat(
                    $content,
                    $modelId,
                    $conversationHistory,
                    $streamCallback,  // Enable streaming
                    $imageData,
                    'chat',
                    $user->id
                );

                if (!$aiResult['success']) {
                    // Send error
                    echo "data: " . json_encode(['error' => $aiResult['error'] ?? 'AI service error']) . "\n\n";
                    flush();
                    echo "data: [DONE]\n\n";
                    flush();
                    return;
                }

                // Use the full response from streaming or fallback to content
                $finalResponse = !empty($fullResponse) ? $fullResponse : ($aiResult['content'] ?? '');

                // Save AI message to database
                $aiMessageRecord = \App\Models\MobileChatMessage::create([
                    'mobile_chat_id' => $chatId,
                    'sender' => 'assistant',
                    'content' => $finalResponse,
                ]);

                // Record usage for successful streaming message
                $usageLimitService = app(\App\Services\UsageLimitService::class);
                $usageLimitService->recordUsage($user, 'ai_doubt');

                // Update chat
                \App\Models\MobileChat::where('id', $chatId)->update([
                    'last_message_at' => now(),
                    'ai_model_id' => $modelId,
                ]);

                // Send completion signal
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
            'message_id' => 'required|string',
            'chat_id' => 'required|string',
            'feedback_type' => 'required|in:like,dislike',
            'message_content' => 'nullable|string',
        ]);

        $user = $request->user();

        try {
            // Store feedback in database for AI optimization
            \DB::table('ai_feedback')->insert([
                'user_id' => $user->id,
                'chat_id' => $request->input('chat_id'),
                'message_id' => $request->input('message_id'),
                'feedback_type' => $request->input('feedback_type'),
                'message_content' => $request->input('message_content'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            \Log::info('AI feedback saved', [
                'user_id' => $user->id,
                'feedback_type' => $request->input('feedback_type'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Feedback saved successfully',
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to save feedback', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => true, // Return success anyway to not affect UX
                'message' => 'Feedback noted',
            ]);
        }
    }

    /**
     * Auto-detect language from message content
     * Returns: 'hindi' (Devanagari), 'hinglish' (Roman Hindi), or 'english'
     */
    private function detectLanguageFromMessage(string $message): string
    {
        if (empty($message)) {
            return 'hinglish'; // Default for empty messages
        }

        // Check for Devanagari script (Hindi)
        if (preg_match('/[\x{0900}-\x{097F}]/u', $message)) {
            return 'hindi';
        }

        // Check for Hinglish (Hindi words in Roman script)
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

        // If 2+ Hinglish words found, it's Hinglish
        if ($hinglishCount >= 2) {
            return 'hinglish';
        }

        return 'english';
    }
}
