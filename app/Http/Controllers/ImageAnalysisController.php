<?php

namespace App\Http\Controllers;

use App\Models\ImageAnalysis;
use App\Services\ImageService;
use App\Services\OpenAIService;
use App\Services\RAGService;
use App\Services\FileStorageService;
use App\Services\UsageTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageAnalysisController extends Controller
{
    protected OpenAIService $openAIService;
    protected ImageService $imageService;
    protected RAGService $ragService;
    protected FileStorageService $fileStorageService;

    public function __construct(OpenAIService $openAIService, ImageService $imageService, RAGService $ragService, FileStorageService $fileStorageService)
    {
        $this->openAIService = $openAIService;
        $this->imageService = $imageService;
        $this->ragService = $ragService;
        $this->fileStorageService = $fileStorageService;
    }

    /**
     * Analyze uploaded image using OpenAI Vision API
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function analyze(Request $request): JsonResponse
    {
        try {
            // Validate the request
            $request->validate([
                'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:10240', // Max 10MB
                'question' => 'nullable|string|max:1000',
            ]);

            $image = $request->file('image');
            $question = $request->input('question', 'Please analyze this image and explain what you see.');

            // Save locally first (OpenAI needs local file path for processing)
            $filename = Str::uuid() . '_' . time() . '.' . $image->getClientOriginalExtension();
            try {
                $imagePath = $image->storeAs('uploads', $filename);
                if (!$imagePath) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to save image.'
                    ], 500);
                }
            } catch (\Exception $e) {
                Log::error('Image upload failed: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to save image: ' . $e->getMessage()
                ], 500);
            }

            // Get full path for OpenAI processing
            $fullPath = Storage::path($imagePath);

            // Upload to cloud storage if active
            $cloudUrl = null;
            $storedPath = $imagePath;
            if ($this->fileStorageService->isActive()) {
                try {
                    $uploadResult = $this->fileStorageService->upload($image, 'image-analysis');
                    if ($uploadResult) {
                        $cloudUrl = $uploadResult['file_url'];
                        $storedPath = $uploadResult['file_path'];
                    }
                } catch (\Exception $e) {
                    Log::warning('Cloud upload failed, using local: ' . $e->getMessage());
                }
            }

            // Get user's class level
            $user = Auth::user();
            $classLevel = $user && $user->student_class ? $user->student_class : null;

            // Analyze image using OpenAI Vision API with user's question
            $aiResponse = $this->openAIService->analyzeImage($fullPath, $classLevel, $question);

            // Clean up local file after processing
            Storage::delete($imagePath);

            if (!$aiResponse) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to analyze image. Please try again.'
                ], 500);
            }

            // Save to database (cloud URL if available, otherwise local path)
            $analysis = ImageAnalysis::create([
                'image_path' => $cloudUrl ?? $storedPath,
                'ai_response' => $aiResponse,
                'ip_address' => $request->ip(),
            ]);

            // Track usage (image upload and API call)
            $user = Auth::user();
            if ($user) {
                UsageTrackingService::trackImageUpload($user->id, 1);
                UsageTrackingService::trackApiCall($user->id, 'gpt-4o-vision', 100, ['type' => 'image_analysis'], null);
            }

            // Mark image for deletion (will be cleaned up by cron job)
            // Images older than 60 seconds will be automatically deleted
            // No queue worker needed - perfect for shared hosting!

            return response()->json([
                'success' => true,
                'message' => 'Image analyzed successfully!',
                'data' => [
                    'response' => $aiResponse,
                    'analysis_id' => $analysis->id,
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Image analysis failed: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your request.'
            ], 500);
        }
    }

    /**
     * Analyze uploaded PDF using OpenAI API
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function analyzePdf(Request $request): JsonResponse
    {
        try {
            // Validate the request
            $request->validate([
                'pdf' => 'required|file|mimes:pdf|max:10240', // Max 10MB
                'question' => 'nullable|string|max:1000',
            ]);

            $pdf = $request->file('pdf');
            $question = $request->input('question', 'Please analyze this PDF and summarize its content.');

            // Save locally first (needed for text extraction)
            $filename = Str::uuid() . '_' . time() . '.pdf';
            try {
                $pdfPath = $pdf->storeAs('uploads', $filename);
                if (!$pdfPath) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to save PDF file.'
                    ], 500);
                }
            } catch (\Exception $e) {
                Log::error('PDF upload failed: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to save PDF: ' . $e->getMessage()
                ], 500);
            }

            // Upload to cloud storage if active
            if ($this->fileStorageService->isActive()) {
                try {
                    $this->fileStorageService->upload($pdf, 'pdf-analysis');
                } catch (\Exception $e) {
                    Log::warning('Cloud PDF upload failed: ' . $e->getMessage());
                }
            }

            // Get full path for PDF processing
            $fullPath = Storage::path($pdfPath);

            // Extract text from PDF using basic PHP method (fallback for no dependencies)
            try {
                // Try to read PDF content - basic text extraction
                $pdfContent = file_get_contents($fullPath);

                // Simple PDF text extraction (removes binary data, keeps readable text)
                // This is a basic approach that works for simple PDFs
                $text = '';
                if (preg_match_all('/\(([^\)]+)\)/', $pdfContent, $matches)) {
                    $text = implode(' ', $matches[1]);
                }

                // If no text found, try another pattern
                if (empty(trim($text))) {
                    if (preg_match_all('/\[(.*?)\]/', $pdfContent, $matches)) {
                        $text = implode(' ', $matches[1]);
                    }
                }

                // If still no text, inform user
                if (empty(trim($text))) {
                    Storage::delete($pdfPath);
                    return response()->json([
                        'success' => false,
                        'message' => 'Could not extract text from PDF. The PDF might be image-based or encrypted. Please try a text-based PDF.'
                    ], 400);
                }

                // Clean extracted text
                $text = preg_replace('/[^\x20-\x7E\s]/', '', $text); // Remove non-printable chars
                $text = trim($text);

                // Limit text length (max ~3000 characters for API)
                if (strlen($text) > 3000) {
                    $text = substr($text, 0, 3000) . '...';
                }

            } catch (\Exception $e) {
                Log::error('PDF text extraction failed: ' . $e->getMessage());
                Storage::delete($pdfPath);
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to extract text from PDF: ' . $e->getMessage()
                ], 500);
            }

            // Get user's class level
            $user = Auth::user();
            $classLevel = $user && $user->student_class ? $user->student_class : null;

            // Analyze PDF content using OpenAI
            $prompt = "PDF Content:\n\n{$text}\n\nUser Question: {$question}";
            $aiResponse = $this->openAIService->analyzePdfContent($prompt, $classLevel);

            if (!$aiResponse) {
                // Clean up the uploaded file
                Storage::delete($pdfPath);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to analyze PDF content. Please try again.'
                ], 500);
            }

            // Track usage (PDF upload and API call)
            if ($user) {
                UsageTrackingService::trackImageUpload($user->id, 1); // Track as file upload
                UsageTrackingService::trackApiCall($user->id, 'gpt-4o', 150, ['type' => 'pdf_analysis'], null);
            }

            // Clean up PDF file immediately after processing
            Storage::delete($pdfPath);

            return response()->json([
                'success' => true,
                'message' => 'PDF analyzed successfully!',
                'data' => [
                    'response' => $aiResponse,
                    'extracted_text_length' => strlen($text),
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('PDF analysis failed: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your PDF.'
            ], 500);
        }
    }

    /**
     * Answer text-based question using RAG (Retrieval-Augmented Generation)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function askQuestion(Request $request): JsonResponse
    {
        try {
            // Validate the request
            $request->validate([
                'question' => 'required|string|min:3|max:1000',
                'subject' => 'nullable|string|max:100', // Optional subject filter
            ]);

            $question = $request->input('question');
            $subject = $request->input('subject'); // e.g., "Mathematics", "Science", "History"

            // Get user's class level
            $user = Auth::user();
            $classLevel = $user && $user->student_class ? $user->student_class : null;

            // Step 1: Retrieve relevant context from NCERT materials using RAG
            $ragResult = $this->ragService->getRelevantContext(
                $question,
                $classLevel,
                $subject,
                5, // Top 5 most relevant chunks
                0.6 // Similarity threshold (60%)
            );

            // Step 2: Check if context was found
            if (!$ragResult['success']) {
                // No relevant context found - return the specific message
                return response()->json([
                    'success' => true, // Still success, just no answer
                    'message' => $ragResult['message'],
                    'data' => [
                        'response' => $ragResult['message'],
                        'question' => $question,
                        'context_found' => false,
                        'class_level' => $classLevel,
                        'subject' => $subject
                    ]
                ]);
            }

            // Step 3: Get AI response using the retrieved context
            $aiResponse = $this->openAIService->answerWithContext(
                $question,
                $ragResult['context'],
                $classLevel
            );

            if (!$aiResponse) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to get AI response. Please try again.'
                ], 500);
            }

            // Track usage (message and API call)
            if ($user) {
                $estimatedTokens = (int) (strlen($question) / 4) + 100; // Rough estimate
                UsageTrackingService::trackMessage($user->id, 'gpt-4o', $estimatedTokens);
                UsageTrackingService::trackApiCall($user->id, 'gpt-4o', $estimatedTokens, [
                    'question' => $question,
                    'rag_enabled' => true,
                    'chunks_used' => $ragResult['chunks_count']
                ], null);
            }

            return response()->json([
                'success' => true,
                'message' => 'Question answered successfully!',
                'data' => [
                    'response' => $aiResponse,
                    'question' => $question,
                    'context_found' => true,
                    'sources' => $ragResult['sources'],
                    'chunks_used' => $ragResult['chunks_count'],
                    'class_level' => $classLevel,
                    'subject' => $subject
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('RAG Question answering failed: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your request.'
            ], 500);
        }
    }

    /**
     * Stream chat response using OpenAI API
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function stream(Request $request)
    {
        try {
            $request->validate([
                'message' => 'required|string|min:1|max:5000',
                'model_id' => 'nullable|integer|exists:ai_models,id'
            ]);

            $userMessage = $request->input('message');
            $modelId = $request->input('model_id'); // Get selected AI model
            $history = session('chat_history', []);
            $history[] = ['role' => 'user', 'content' => $userMessage];

            // Get AI model
            $aiModel = $modelId
                ? \App\Models\AiModel::where('id', $modelId)->where('is_active', true)->first()
                : \App\Models\AiModel::where('is_active', true)->orderBy('order')->first();

            if (!$aiModel) {
                return response()->json(['error' => 'No active AI model found'], 400);
            }

            // Get API key from settings
            $apiKey = $this->getApiKeyForProvider($aiModel->provider);

            if (!$apiKey) {
                return response()->json(['error' => ucfirst($aiModel->provider) . ' API key not configured'], 400);
            }

            // Get user's class level and plan
            $user = Auth::user();
            $user->load('userPlan'); // Load the user's pricing plan
            $classLevel = $user && $user->student_class ? $user->student_class : null;
            $userPlan = $user->userPlan;

            // Determine plan type and limits
            $planName = 'BASIC STUDY PLAN';
            $planPrice = '₹499/month';
            $monthlyTokenLimit = '650,000';
            $dailyRequestLimit = '30';
            $avgTokenUsage = '600-800';
            $maxTokensPerResponse = '1000';
            $isPro = false;

            if ($userPlan && stripos($userPlan->name, 'pro') !== false) {
                $planName = 'PRO STUDY PLAN';
                $planPrice = '₹999/month';
                $monthlyTokenLimit = '1,600,000';
                $dailyRequestLimit = '80';
                $avgTokenUsage = '600-800';
                $maxTokensPerResponse = '1200';
                $isPro = true;
            }

            // AI Study Assistant System Prompt - Educational Purposes Only
            $systemPrompt = "You are an AI Study Assistant designed ONLY for educational purposes.\n";
            $systemPrompt .= "This user is subscribed to the {$planName} ({$planPrice}).\n\n";

            $systemPrompt .= "PLAN LIMITS:\n";
            $systemPrompt .= "- Monthly token limit: {$monthlyTokenLimit} tokens\n";
            $systemPrompt .= "- Daily request limit: Maximum {$dailyRequestLimit} requests per day\n";
            $systemPrompt .= "- Average token usage per response: {$avgTokenUsage} tokens\n";
            $systemPrompt .= "- Maximum tokens per response: {$maxTokensPerResponse} tokens\n";
            $systemPrompt .= "- You must strictly follow these limits and optimize accordingly.\n\n";

            $systemPrompt .= "CORE RESPONSIBILITIES:\n";
            $systemPrompt .= "- Provide academic support for Indian school students (Classes 1-10, CBSE/NCERT)\n";
            $systemPrompt .= "- Optimize responses to save tokens while maintaining clarity\n";

            if ($isPro) {
                $systemPrompt .= "- Provide detailed but efficient educational explanations\n";
                $systemPrompt .= "- Maintain a professional, tutor-like tone\n";
                $systemPrompt .= "- This plan is for intensive learners and competitive exam preparation\n\n";
            } else {
                $systemPrompt .= "- Always be concise and to the point\n";
                $systemPrompt .= "- Never generate unnecessarily lengthy explanations\n";
                $systemPrompt .= "- This plan is optimized for regular students and exam preparation\n\n";
            }

            $systemPrompt .= "STRICT OUTPUT RULES (VERY IMPORTANT):\n";
            $systemPrompt .= "1. Do NOT use LaTeX symbols like \\( \\), \\[ \\], ^{}, or any math formatting.\n";
            $systemPrompt .= "2. Do NOT use Markdown, headings, bold text, bullets, or special formatting.\n";
            $systemPrompt .= "3. Write all mathematics in simple plain text.\n";
            $systemPrompt .= "4. Use only clear steps like: Step 1, Step 2, Step 3.\n";
            $systemPrompt .= "5. Keep language simple, like a school teacher explaining in an exam copy.\n";
            $systemPrompt .= "6. Do not ask follow-up questions unless the student clearly asks.\n";
            $systemPrompt .= "7. Do not give unnecessary theory. Be clear and to the point.\n";
            $systemPrompt .= "8. Keep responses SHORT and CONCISE to save tokens.\n";
            $systemPrompt .= "9. Always write the final result clearly using:\n   Final Answer:\n\n";

            $systemPrompt .= "MATH RULES:\n";
            $systemPrompt .= "- Always mention the rule used (product rule, chain rule, etc.)\n";
            $systemPrompt .= "- Show working step by step, but concisely.\n";
            $systemPrompt .= "- Answer must look like CBSE board exam solution.\n";
            $systemPrompt .= "- Avoid lengthy explanations unless specifically asked.\n\n";

            $systemPrompt .= "SCIENCE RULES:\n";
            $systemPrompt .= "- Use short explanations.\n";
            $systemPrompt .= "- Write definitions in one or two lines.\n";
            $systemPrompt .= "- If diagrams are needed, explain in words briefly.\n";
            $systemPrompt .= "- Keep answers concise and exam-focused.\n\n";

            $systemPrompt .= "BEHAVIOUR RULES:\n";

            if ($isPro) {
                // PRO PLAN Behavior
                $systemPrompt .= "- Provide detailed but efficient educational explanations\n";
                $systemPrompt .= "- Use headings, bullet points, and examples only when useful\n";
                $systemPrompt .= "- Avoid storytelling or casual conversation\n";
                $systemPrompt .= "- Maintain a professional, tutor-like tone\n";
                $systemPrompt .= "- Optimize responses to minimize token usage\n";
            } else {
                // BASIC PLAN Behavior
                $systemPrompt .= "- Provide clear, short, and structured educational answers\n";
                $systemPrompt .= "- Prefer bullet points and step-by-step explanations\n";
                $systemPrompt .= "- Avoid unnecessary examples unless required\n";
                $systemPrompt .= "- If the user asks for very long answers, summarize instead\n";
            }

            $systemPrompt .= "- Do NOT ask follow-up questions unless absolutely necessary\n";
            $systemPrompt .= "- If the question is unclear, assume the most common exam meaning\n";
            $systemPrompt .= "- Never say \"please specify\" for basic exam questions\n";
            $systemPrompt .= "- Never refuse simple academic questions\n\n";

            $systemPrompt .= "TOKEN OPTIMIZATION (CRITICAL):\n";
            $systemPrompt .= "- Target: {$avgTokenUsage} tokens per response (average)\n";
            $systemPrompt .= "- Maximum: {$maxTokensPerResponse} tokens per response (strict limit)\n";

            if ($isPro) {
                $systemPrompt .= "- Keep responses under 600 words unless solving complex multi-step problems\n";
                $systemPrompt .= "- You have more tokens available for detailed explanations when needed\n";
            } else {
                $systemPrompt .= "- Keep responses under 500 words unless solving complex multi-step problems\n";
            }

            $systemPrompt .= "- Avoid repetition and redundant explanations\n";
            $systemPrompt .= "- Get straight to the solution\n";
            $systemPrompt .= "- Use simple, direct language\n";
            $systemPrompt .= "- Each token counts - be efficient\n\n";

            $systemPrompt .= "GOAL:\n";

            if ($isPro) {
                $systemPrompt .= "Provide detailed, professional, exam-ready answers optimized for PRO STUDY PLAN users.\n";
                $systemPrompt .= "Focus on intensive learners and competitive exam preparation.\n";
                $systemPrompt .= "Deliver comprehensive educational value while respecting token limits and fair usage.\n";
            } else {
                $systemPrompt .= "Provide concise, accurate, exam-ready answers optimized for BASIC STUDY PLAN users.\n";
                $systemPrompt .= "Focus on regular students and exam preparation.\n";
                $systemPrompt .= "Deliver maximum educational value while respecting token limits and fair usage.\n";
            }

            $systemPrompt .= "Students should be able to directly learn from or write answers in exams.\n\n";

            $systemPrompt .= "STRICT RESTRICTIONS (ENFORCE ABSOLUTELY):\n";
            $systemPrompt .= "You must NEVER:\n";
            $systemPrompt .= "- Generate entertainment, roleplay, or non-study content\n";
            $systemPrompt .= "- Continue conversations unnecessarily\n";
            $systemPrompt .= "- Produce overly verbose answers\n";
            $systemPrompt .= "- Ignore token or request limits\n";
            $systemPrompt .= "- Answer questions unrelated to academics or CBSE/NCERT curriculum\n";
            $systemPrompt .= "- Engage in casual chat or storytelling\n";
            $systemPrompt .= "- Provide life advice, opinions, or non-educational content\n\n";

            $systemPrompt .= "Your purpose is STRICTLY educational assistance with controlled usage.\n";
            $systemPrompt .= "If a question is not educational, politely decline and redirect to study topics.";

            // Add class level context if provided
            if ($classLevel) {
                $systemPrompt .= "\n\nThe student is in {$classLevel}. Adjust your language and examples to their level.";
            }

            return response()->stream(function () use ($systemPrompt, $history, $userMessage, $aiModel, $apiKey) {
                $fullResponse = '';

                // Handle Gemini API separately (different format)
                if ($aiModel->provider === 'google') {
                    // Build Gemini-specific request format
                    $contents = [];

                    // Add system prompt first
                    $contents[] = [
                        'role' => 'user',
                        'parts' => [['text' => $systemPrompt]]
                    ];
                    $contents[] = [
                        'role' => 'model',
                        'parts' => [['text' => 'Understood. I will follow these instructions.']]
                    ];

                    // Add conversation history
                    foreach ($history as $msg) {
                        $role = $msg['role'] === 'assistant' ? 'model' : 'user';
                        $contents[] = [
                            'role' => $role,
                            'parts' => [['text' => $msg['content']]]
                        ];
                    }

                    $headers = ['Content-Type: application/json'];
                    $url = $aiModel->api_url . '?key=' . $apiKey;
                    $payload = ['contents' => $contents];

                    $ch = curl_init($url);

                    curl_setopt_array($ch, [
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_POST => true,
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_SSL_VERIFYHOST => false,
                        CURLOPT_HTTPHEADER => $headers,
                        CURLOPT_POSTFIELDS => json_encode($payload),
                    ]);

                    $rawResponse = curl_exec($ch);
                    $curlError = curl_error($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);

                    // Log the request and response for debugging
                    Log::info('Gemini API Request', [
                        'url' => substr($url, 0, 100) . '...',
                        'payload' => $payload,
                        'http_code' => $httpCode,
                        'response_length' => strlen($rawResponse ?? ''),
                        'error' => $curlError,
                    ]);

                    if ($curlError) {
                        Log::error('Gemini curl error', ['error' => $curlError]);
                        echo "data: " . json_encode(['error' => 'Connection error: ' . $curlError]) . "\n\n";
                    } elseif ($httpCode === 401) {
                        Log::error('Gemini 401 error', [
                            'response' => substr($rawResponse, 0, 500),
                            'api_key_prefix' => substr($apiKey, 0, 10) . '...'
                        ]);
                        echo "data: " . json_encode(['error' => 'API authentication failed. Check your Gemini API key in admin panel.']) . "\n\n";
                    } elseif ($httpCode !== 200) {
                        Log::error('Gemini API error', [
                            'http_code' => $httpCode,
                            'response' => substr($rawResponse, 0, 1000)
                        ]);
                        echo "data: " . json_encode(['error' => 'Gemini API error (HTTP ' . $httpCode . ')']) . "\n\n";
                    } else {
                        // Parse the raw streaming response
                        $lines = explode("\n", $rawResponse);
                        foreach ($lines as $line) {
                            $line = trim($line);
                            if (empty($line)) {
                                continue;
                            }

                            $decoded = json_decode($line, true);
                            if ($decoded && isset($decoded['candidates'][0]['content']['parts'][0]['text'])) {
                                $content = $decoded['candidates'][0]['content']['parts'][0]['text'];
                                $fullResponse .= $content;
                                echo "data: " . json_encode(['content' => $content]) . "\n\n";
                                if (ob_get_level() > 0) {
                                    ob_flush();
                                }
                                flush();
                            }
                        }

                        // Save to session
                        if (!empty($fullResponse)) {
                            $history[] = ['role' => 'assistant', 'content' => $fullResponse];
                            session(['chat_history' => $history]);

                            $user = Auth::user();
                            if ($user) {
                                $estimatedTokens = (int) ((strlen($userMessage) + strlen($fullResponse)) / 4);
                                UsageTrackingService::trackMessage($user->id, $aiModel->model_identifier, $estimatedTokens);
                                UsageTrackingService::trackApiCall($user->id, $aiModel->model_identifier, $estimatedTokens, ['type' => 'chat_stream'], null);
                            }
                        } else {
                            echo "data: " . json_encode(['error' => 'Empty response from Gemini']) . "\n\n";
                        }
                    }

                    echo "data: [DONE]\n\n";
                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();
                    return;
                }

                // Handle other providers (OpenAI, Claude, DeepSeek) - Original code
                $messages = [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ...$history
                ];

                // Build headers based on provider
                $headers = ['Content-Type: application/json'];

                if ($aiModel->provider === 'anthropic') {
                    $headers[] = 'x-api-key: ' . $apiKey;
                    $headers[] = 'anthropic-version: 2023-06-01';
                } else {
                    $headers[] = 'Authorization: Bearer ' . $apiKey;
                }

                // Build request payload based on provider
                $payload = [
                    'model' => $aiModel->model_identifier,
                    'temperature' => 0.5,
                    'stream' => true,
                ];

                if ($aiModel->provider === 'anthropic') {
                    $payload['max_tokens'] = (int) ($aiModel->max_tokens ?? 3000);
                    $payload['messages'] = $messages;
                } else {
                    $payload['messages'] = $messages;
                    $payload['max_tokens'] = (int) ($aiModel->max_tokens ?? 3000);
                }

                $ch = curl_init($aiModel->api_url);

                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => false,
                    CURLOPT_POST => true,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_HTTPHEADER => $headers,
                    CURLOPT_POSTFIELDS => json_encode($payload),
                    CURLOPT_WRITEFUNCTION => function($curl, $data) use (&$fullResponse, $aiModel) {
                        $lines = explode("\n", $data);

                        foreach ($lines as $line) {
                            if (trim($line) === '' || trim($line) === 'data: [DONE]') {
                                continue;
                            }

                            if (strpos($line, 'data: ') === 0) {
                                $json = substr($line, 6);
                                $decoded = json_decode($json, true);

                                $content = null;

                                // Handle different provider response formats
                                if ($aiModel->provider === 'anthropic') {
                                    // Anthropic format: delta.text or content[0].text
                                    if (isset($decoded['delta']['text'])) {
                                        $content = $decoded['delta']['text'];
                                    } elseif (isset($decoded['content'][0]['text'])) {
                                        $content = $decoded['content'][0]['text'];
                                    }
                                } else {
                                    // OpenAI/DeepSeek format: choices[0].delta.content
                                    if (isset($decoded['choices'][0]['delta']['content'])) {
                                        $content = $decoded['choices'][0]['delta']['content'];
                                    }
                                }

                                if ($content !== null) {
                                    $fullResponse .= $content;
                                    echo "data: " . json_encode(['content' => $content]) . "\n\n";
                                    if (ob_get_level() > 0) {
                                        ob_flush();
                                    }
                                    flush();
                                }
                            }
                        }

                        return strlen($data);
                    }
                ]);

                curl_exec($ch);
                $curlError = curl_error($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if (!empty($curlError)) {
                    Log::error('AI API connection error', [
                        'provider' => $aiModel->provider,
                        'model' => $aiModel->model_identifier,
                        'error' => $curlError
                    ]);
                    echo "data: " . json_encode(['error' => 'Connection error: ' . $curlError]) . "\n\n";
                } elseif ($httpCode === 401) {
                    Log::error('AI API authentication failed', [
                        'provider' => $aiModel->provider,
                        'model' => $aiModel->model_identifier,
                        'http_code' => $httpCode,
                        'api_key_prefix' => substr($apiKey, 0, 10) . '...'
                    ]);
                    echo "data: " . json_encode(['error' => ucfirst($aiModel->provider) . ' API key is invalid or expired. Please check your API key configuration in settings.']) . "\n\n";
                } elseif ($httpCode === 400) {
                    Log::error('AI API bad request', [
                        'provider' => $aiModel->provider,
                        'model' => $aiModel->model_identifier,
                        'http_code' => $httpCode,
                        'payload' => json_encode($payload),
                        'url' => $aiModel->api_url
                    ]);
                    echo "data: " . json_encode(['error' => 'Invalid request to ' . ucfirst($aiModel->provider) . '. Please check your API configuration in admin settings.']) . "\n\n";
                } elseif ($httpCode !== 200) {
                    Log::error('AI API error', [
                        'provider' => $aiModel->provider,
                        'model' => $aiModel->model_identifier,
                        'http_code' => $httpCode
                    ]);
                    echo "data: " . json_encode(['error' => ucfirst($aiModel->provider) . ' API error (HTTP ' . $httpCode . '). Please try again or contact support.']) . "\n\n";
                } elseif (empty($fullResponse)) {
                    echo "data: " . json_encode(['error' => 'No response received from AI. Please try again.']) . "\n\n";
                } else {
                    $history[] = ['role' => 'assistant', 'content' => $fullResponse];
                    session(['chat_history' => $history]);

                    // Debug: Log session save
                    Log::info('Chat history saved to session', [
                        'history_count' => count($history),
                        'session_id' => session()->getId(),
                        'last_message_preview' => substr($fullResponse, 0, 100)
                    ]);

                    $user = Auth::user();
                    if ($user) {
                        $estimatedTokens = (int) ((strlen($userMessage) + strlen($fullResponse)) / 4);
                        UsageTrackingService::trackMessage($user->id, $aiModel->model_identifier, $estimatedTokens);
                        UsageTrackingService::trackApiCall($user->id, $aiModel->model_identifier, $estimatedTokens, ['type' => 'chat_stream'], null);
                    }
                }

                echo "data: [DONE]\n\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            }, 200, [
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache',
                'X-Accel-Buffering' => 'no',
                'Connection' => 'keep-alive',
            ]);

        } catch (\Exception $e) {
            Log::error('Chat streaming failed: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your request.'
            ], 500);
        }
    }

    /**
     * Clear chat history
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function clearChat(Request $request): JsonResponse
    {
        session()->forget('chat_history');

        return response()->json([
            'success' => true,
            'message' => 'Chat history cleared'
        ]);
    }

    /**
     * Enhance user prompt using AI
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function enhancePrompt(Request $request): JsonResponse
    {
        try {
            // Validate the request
            $request->validate([
                'prompt' => 'required|string|min:2|max:500',
            ]);

            $originalPrompt = $request->input('prompt');

            // Use AI to enhance the prompt
            $enhancedPrompt = $this->openAIService->enhancePrompt($originalPrompt);

            if (!$enhancedPrompt) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to enhance prompt. Please try again.'
                ], 500);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'original' => $originalPrompt,
                    'enhanced' => $enhancedPrompt,
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Prompt enhancement failed: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while enhancing your prompt.'
            ], 500);
        }
    }

    /**
     * Get API key for AI provider from admin panel settings (FrontendConfig)
     *
     * @param string $provider
     * @return string|null
     */
    protected function getApiKeyForProvider(string $provider): ?string
    {
        $key = match ($provider) {
            'openai' => 'ai.openai_api_key',
            'anthropic' => 'ai.claude_api_key',
            'deepseek' => 'ai.deepseek_api_key',
            'google' => 'ai.gemini_api_key',
            'xai' => 'ai.grok_api_key',
            default => null,
        };

        if (!$key) {
            return null;
        }

        // Get from FrontendConfig (admin panel settings)
        $apiKey = \App\Models\FrontendConfig::getValue($key, '');

        return $apiKey ?: null;
    }
}
