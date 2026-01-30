<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use App\Services\CreditService;
use App\Services\UnifiedAIService;
use App\Services\FileStorageService;
use App\Models\FrontendConfig;

class MobileChatController extends Controller
{
    private $creditService;
    private $aiService;
    private $fileStorageService;

    public function __construct(CreditService $creditService, UnifiedAIService $aiService, FileStorageService $fileStorageService)
    {
        $this->creditService = $creditService;
        $this->aiService = $aiService;
        $this->fileStorageService = $fileStorageService;
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
        // Log incoming request for debugging
        \Log::info('=== sendMessage called ===', [
            'chat_id' => $chatId,
            'has_file' => $request->hasFile('file'),
            'content' => $request->input('content'),
            'ai_model_id' => $request->input('ai_model_id'),
            'all_input' => $request->all(),
        ]);

        // If file exists, log file details
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            \Log::info('File details:', [
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'extension' => $file->getClientOriginalExtension(),
                'valid' => $file->isValid(),
                'error' => $file->getError(),
            ]);
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
        ]);

        $user = auth()->user();
        $content = $request->input('content', '');
        $modelId = $request->input('ai_model_id');
        $hasImage = $request->hasFile('file');

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
        // STEP 2: CREDIT CHECK
        // =============================================
        // Wrap in try-catch to handle database errors gracefully
        try {
            // Initialize user credits if first time
            $this->creditService->initializeUserCredits($user);

            // Determine action cost (1 credit normal, 2 with image)
            $action = $hasImage ? 'chat_message_with_image' : 'chat_message';

            // Check if user can perform this action
            $creditCheck = $this->creditService->canPerformAction($user, $action);

            if (!$creditCheck['has_credits']) {
                // Return error in array format for consistency
                $errorMessage = [
                    'id' => time(),
                    'chat_id' => $chatId,
                    'sender' => 'system',
                    'content' => "⚠️ {$creditCheck['reason']}. You have {$creditCheck['balance']} credits, but need {$creditCheck['cost']}. Please upgrade your plan to continue.",
                    'error' => 'insufficient_credits',
                    'credits' => [
                        'balance' => $creditCheck['balance'],
                        'required' => $creditCheck['cost'],
                    ],
                    'created_at' => now()->toISOString(),
                ];

                // Return 200 so mobile app doesn't throw error, but include error in message
                return response()->json([$errorMessage], 200);
            }

            $shouldDeductCredits = true;
        } catch (\Exception $e) {
            // If credit system fails (database error, etc), log it and continue
            \Log::error('Credit system error - allowing message without credit check', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            // Skip credit deduction if system is unavailable
            $shouldDeductCredits = false;
            $action = 'chat_message'; // Set default action
            $creditCheck = ['cost' => 0]; // Set cost to 0
        }

        // =============================================
        // STEP 3: INCREMENT RATE LIMITER
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
            // Get conversation history for context
            $conversationHistory = \App\Models\MobileChatMessage::where('mobile_chat_id', $chatId)
                ->where('id', '!=', $userMessageRecord->id)  // Exclude the message we just created
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->reverse()
                ->map(fn($msg) => [
                    'role' => $msg->sender === 'user' ? 'user' : 'assistant',
                    'content' => $msg->content
                ])
                ->toArray();

            // Log the request for debugging
            \Log::info('AI Service Request', [
                'user_id' => $user->id,
                'chat_id' => $chatId,
                'model_id' => $modelId,
                'message' => substr($content, 0, 100),
                'history_count' => count($conversationHistory),
            ]);

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

            // Use UnifiedAIService to get response with optimized settings
            $aiResult = $this->aiService->chat(
                $content,
                $modelId,  // Use the selected model ID from request
                $conversationHistory,
                null,  // No streaming for now
                $imageData,  // Pass image data for vision analysis
                $feature,    // Feature for optimization
                $user->id    // User ID for tracking
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
        // STEP 6: DEDUCT CREDITS ON SUCCESS (if credit system is working)
        // =============================================
        if ($shouldDeductCredits) {
            try {
                $deductionResult = $this->creditService->deductCredits(
                    $user,
                    $action,
                    null, // Use default cost
                    "Chat message in chat #{$chatId}",
                    null, // No reference type for now (can add Chat model later)
                    $chatId
                );
            } catch (\Exception $e) {
                // If deduction fails, log but continue
                \Log::error('Credit deduction failed', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
                $deductionResult = ['success' => false, 'new_balance' => 0];
            }
        } else {
            // Credit system unavailable - set dummy result
            $deductionResult = ['success' => false, 'new_balance' => 0];
        }

        // =============================================
        // STEP 7: CREATE AI MESSAGE
        // =============================================

        // Save AI message to database
        $aiMessageRecord = \App\Models\MobileChatMessage::create([
            'mobile_chat_id' => $chatId,
            'sender' => 'assistant',
            'content' => $aiResponse,
            'metadata' => [
                'credits_deducted' => $deductionResult['success'],
                'cost' => $creditCheck['cost'],
                'new_balance' => $deductionResult['new_balance'],
            ],
        ]);

        $aiMessage = [
            'id' => $aiMessageRecord->id,
            'chat_id' => $chatId,
            'sender' => 'assistant',
            'content' => $aiResponse,
            'created_at' => $aiMessageRecord->created_at->toIso8601String(),
            // Add credit info to AI message (optional, can be used by UI)
            'credits' => [
                'deducted' => $deductionResult['success'],
                'cost' => $creditCheck['cost'],
                'new_balance' => $deductionResult['new_balance'],
            ],
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
     * Get chat messages
     */
    public function getMessages($chatId)
    {
        $user = auth()->user();

        // Verify chat belongs to user
        $chat = \App\Models\MobileChat::where('id', $chatId)
            ->where('user_id', $user->id)
            ->first();

        if (!$chat) {
            return response()->json(['error' => 'Chat not found'], 404);
        }

        // Get all messages for this chat
        $messages = $chat->messages->map(function ($message) {
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
     * Get all chats
     */
    public function getChats()
    {
        $user = auth()->user();

        // Get all user's chats ordered by last_message_at (latest first)
        $chats = \App\Models\MobileChat::where('user_id', $user->id)
            ->orderBy('last_message_at', 'desc')
            ->orderBy('created_at', 'desc')
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
}
