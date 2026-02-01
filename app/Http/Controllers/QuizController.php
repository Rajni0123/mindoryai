<?php

namespace App\Http\Controllers;

use App\Models\Notebook;
use App\Models\Quiz;
use App\Models\QuizCache;
use App\Services\UnifiedAIService;
use App\Services\QuizPdfGenerator;
use App\Services\StudentDoubtSolverService;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class QuizController extends Controller
{
    private UnifiedAIService $unifiedAIService;
    private QuizPdfGenerator $pdfGenerator;

    public function __construct(
        UnifiedAIService $unifiedAIService,
        QuizPdfGenerator $pdfGenerator
    )
    {
        $this->unifiedAIService = $unifiedAIService;
        $this->pdfGenerator = $pdfGenerator;
    }

    /**
     * Generate quiz from chat interface
     * Can generate from notes (notebook) or from current chat context
     */
    public function generateFromChat(Request $request)
    {
        $request->validate([
            'source' => 'required|in:notes,chat',
            'question_count' => 'sometimes|integer|min:5|max:50',
            'difficulty' => 'sometimes|in:easy,medium,hard',
            'notebook_id' => 'sometimes|integer|exists:notebooks,id',
        ]);

        $source = $request->get('source');
        $questionCount = $request->get('question_count', 10);
        $difficulty = $request->get('difficulty', 'medium');

        try {
            if ($source === 'notes') {
                // Generate quiz from notebook/notes
                $user = Auth::user();

                // Get the notebook to use
                if ($request->has('notebook_id')) {
                    $notebook = Notebook::find($request->get('notebook_id'));

                    if (!$notebook || $notebook->user_id !== $user->id) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Notebook not found or access denied.'
                        ], 403);
                    }
                } else {
                    // Get or create default notebook
                    $notebook = $user->notebooks()->latest('updated_at')->first();

                    if (!$notebook) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Please scan some notes first before generating a quiz.'
                        ], 400);
                    }
                }

                // Check if notebook has documents
                if ($notebook->documents()->count() === 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Please scan some notes first before generating a quiz.'
                    ], 400);
                }

                // Check if documents are processed
                $processedDocs = $notebook->documents()->where('processing_status', 'completed')->count();
                if ($processedDocs === 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Please wait for your notes to finish processing before generating a quiz.'
                    ], 400);
                }

                // Generate quiz using StudentDoubtSolverService
                $doubtSolverService = new StudentDoubtSolverService();

                // Get notebook content as topic
                $notebookContent = $notebook->documents()->pluck('content')->implode("\n\n");
                $topic = $notebook->title ?: 'Notebook Content';

                $quizResponse = $doubtSolverService->generateQuiz(
                    $topic . "\n\nContent:\n" . substr($notebookContent, 0, 2000),
                    null,
                    $questionCount,
                    $difficulty
                );

                $quizData = $this->parseQuizResponse($quizResponse, 'mcq');

                if (!$quizData || empty($quizData['questions'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to generate quiz. Please try again.'
                    ], 400);
                }

                return response()->json([
                    'success' => true,
                    'quiz' => [
                        'title' => "Quiz: {$topic}",
                        'description' => 'Generated from your notes',
                        'questions' => $quizData['questions'],
                        'total_questions' => count($quizData['questions']),
                        'difficulty' => $difficulty,
                    ]
                ]);

            } elseif ($source === 'chat') {
                // Generate quiz from current chat context
                $user = Auth::user();
                $notebook = $user->notebooks()->latest('updated_at')->first();

                if (!$notebook || $notebook->documents()->count() === 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'To generate a quiz from chat, please first scan some notes or have a conversation about a topic.'
                    ], 400);
                }

                // Generate quiz using StudentDoubtSolverService
                $doubtSolverService = new StudentDoubtSolverService();

                $notebookContent = $notebook->documents()->pluck('content')->implode("\n\n");
                $topic = $notebook->title ?: 'Chat Content';

                $quizResponse = $doubtSolverService->generateQuiz(
                    $topic . "\n\nContent:\n" . substr($notebookContent, 0, 2000),
                    null,
                    $questionCount,
                    $difficulty
                );

                $quizData = $this->parseQuizResponse($quizResponse, 'mcq');

                if (!$quizData || empty($quizData['questions'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to generate quiz. Please try again.'
                    ], 400);
                }

                return response()->json([
                    'success' => true,
                    'quiz' => [
                        'title' => "Quiz: {$topic}",
                        'description' => 'Generated from chat context',
                        'questions' => $quizData['questions'],
                        'total_questions' => count($quizData['questions']),
                        'difficulty' => $difficulty,
                    ]
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Quiz generation error from chat', [
                'error' => $e->getMessage(),
                'source' => $source,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while generating the quiz. Please try again.'
            ], 500);
        }
    }

    /**
     * Get available quizzes for the user
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $quizzes = $user->quizzes()->latest()->get();

        return response()->json([
            'success' => true,
            'quizzes' => $quizzes
        ]);
    }

    /**
     * Get a specific quiz
     */
    public function show(Request $request, Quiz $quiz)
    {
        if ($quiz->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Quiz not found.'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'quiz' => $quiz
        ]);
    }

    /**
     * Generate quiz from image with caching
     * STRICTLY uses image content only - no text prompt needed
     * Caches results based on image hash for faster responses on repeat images
     */
    public function generateFromImage(Request $request)
    {
        $request->validate([
            'image' => 'required|file|mimes:jpeg,jpg,png,gif,webp,pdf|max:51200', // Max 50MB
            'quiz_type' => 'sometimes|in:mcq,true_false,short_answer',
            'question_count' => 'sometimes|integer|min:3|max:50', // Increased to 50 for books
            'difficulty' => 'sometimes|integer|min:1|max:5',
            'book_mode' => 'sometimes|boolean', // For processing entire books
            'generate_pdf' => 'sometimes|boolean', // Generate downloadable PDF
        ]);

        try {
            $user = Auth::user();
            $file = $request->file('image');
            $quizType = $request->input('quiz_type', 'mcq');
            $questionCount = $request->input('question_count', 5);
            $difficulty = $request->input('difficulty', 2);
            $bookMode = $request->input('book_mode', false);
            $generatePdf = $request->input('generate_pdf', false);

            // Credits removed — access controlled by plan limits

            // Calculate image hash for caching (SHA-256)
            $imageHash = hash_file('sha256', $file->getRealPath());

            // Check if quiz exists in cache
            $cachedQuiz = QuizCache::findByImageHash($imageHash);

            if ($cachedQuiz) {
                \Log::info('Quiz cache hit', [
                    'user_id' => $user->id,
                    'image_hash' => $imageHash,
                    'cached_quiz_id' => $cachedQuiz->id,
                ]);

                // Mark as used and return cached quiz
                $cachedQuiz->markAsUsed();

                return response()->json([
                    'success' => true,
                    'cached' => true,
                    'book_mode' => $bookMode,
                    'pdf_available' => true,
                    'quiz' => [
                        'id' => $cachedQuiz->id,
                        'title' => $bookMode ? 'Quiz from Book' : 'Quiz from Image',
                        'description' => $bookMode ? 'Generated from uploaded book/document (cached)' : 'Generated from uploaded image (cached)',
                        'questions' => $cachedQuiz->quiz_data['questions'] ?? [],
                        'total_questions' => $cachedQuiz->question_count,
                        'difficulty' => $cachedQuiz->difficulty,
                        'quiz_type' => $cachedQuiz->quiz_type,
                        'subject' => $cachedQuiz->subject,
                        'topics' => $cachedQuiz->topics,
                    ],
                    'pdf_download' => [
                        'endpoint' => '/api/quiz/download-pdf',
                        'params' => [
                            'quiz_cache_id' => $cachedQuiz->id,
                            'show_answers' => true,
                            'show_explanations' => true,
                        ]
                    ]
                ]);
            }

            \Log::info('Quiz cache miss - generating new quiz', [
                'user_id' => $user->id,
                'image_hash' => $imageHash,
                'file_type' => $file->getMimeType(),
            ]);

            // Get quiz-specific model from settings (configured in /admin/quiz-generator/settings)
            $quizModelId = \App\Models\Setting::get('quiz_generator_model_id', 83);

            // Handle different file types
            $fileMimeType = $file->getMimeType();
            $imageData = null;
            $prompt = '';

            if ($fileMimeType === 'application/pdf') {
                // PDF file - extract text and generate quiz from text
                $fileSizeMB = $file->getSize() / 1024 / 1024;

                // Adjust limits based on book mode
                $maxSizeMB = $bookMode ? 30 : 15; // Allow larger files in book mode
                $maxPagesLimit = $bookMode ? 100 : 20; // Process more pages in book mode
                $memoryLimit = $bookMode ? '1024M' : '512M'; // More memory for books
                $timeLimit = $bookMode ? 120 : 60; // More time for books

                // Check if PDF is too large
                if ($fileSizeMB > $maxSizeMB) {
                    throw new \Exception("PDF file is too large (" . round($fileSizeMB, 2) . " MB). Please use a PDF under {$maxSizeMB}MB" . ($bookMode ? "" : " or enable book mode for larger files") . ".");
                }

                try {
                    \Log::info('PDF detected for quiz generation', [
                        'file_size_mb' => round($fileSizeMB, 2),
                        'book_mode' => $bookMode,
                        'max_pages' => $maxPagesLimit,
                        'memory_limit' => $memoryLimit,
                    ]);

                    // Set high memory limit and timeout (don't restore - causes errors)
                    ini_set('memory_limit', $memoryLimit);
                    ini_set('max_execution_time', $timeLimit);

                    // Parse with config to reduce memory
                    $config = new \Smalot\PdfParser\Config();
                    $config->setRetainImageContent(false);
                    $parser = new \Smalot\PdfParser\Parser([], $config);

                    $pdf = $parser->parseFile($file->getRealPath());

                    // Extract text from pages based on mode
                    $pages = $pdf->getPages();
                    $totalPages = count($pages);
                    $maxPages = min($maxPagesLimit, $totalPages);
                    $text = '';

                    \Log::info('Processing PDF pages', [
                        'total_pages' => $totalPages,
                        'processing_pages' => $maxPages,
                        'book_mode' => $bookMode,
                    ]);

                    for ($i = 0; $i < $maxPages; $i++) {
                        try {
                            $pageText = $pages[$i]->getText();
                            $text .= $pageText . "\n\n"; // Double newline between pages
                        } catch (\Exception $e) {
                            \Log::warning("Failed to extract page $i", ['error' => $e->getMessage()]);
                            continue;
                        }
                    }

                    // Don't restore limits - keep high limits for remainder of request
                    // (Restoring fails if memory usage is already higher than original limit)

                    // Clean up extracted text
                    $text = preg_replace('/\s+/', ' ', $text);
                    $text = trim($text);

                    if (empty($text)) {
                        throw new \Exception('Could not extract text from PDF. The PDF may be scanned/image-based or password protected. Please use a text-based PDF or convert pages to images (JPG/PNG) instead.');
                    }

                    // Check minimum text length
                    if (strlen($text) < 100) {
                        throw new \Exception('Insufficient text found in PDF (' . strlen($text) . ' characters). Please ensure the PDF contains enough educational content for quiz generation.');
                    }

                    // Create text-based prompt
                    $prompt = $this->getTextQuizPrompt($text, $quizType, $questionCount, $difficulty);

                    \Log::info('PDF text extracted', [
                        'text_length' => strlen($text),
                        'text_preview' => substr($text, 0, 200),
                    ]);

                } catch (\Exception $e) {
                    \Log::error('PDF processing failed for quiz', [
                        'error' => $e->getMessage(),
                        'file_size_mb' => round($file->getSize() / 1024 / 1024, 2),
                        'error_class' => get_class($e),
                    ]);

                    // Check if it's a memory error
                    $errorMsg = strtolower($e->getMessage());
                    if (strpos($errorMsg, 'memory') !== false ||
                        strpos($errorMsg, 'exhausted') !== false ||
                        strpos($errorMsg, 'allocation') !== false) {

                        throw new \Exception('PDF is too complex (' . round($file->getSize() / 1024 / 1024, 2) . ' MB). Please convert pages to images (JPG/PNG), split into smaller files, or use a simpler PDF.');
                    }

                    throw new \Exception('Failed to process PDF: ' . $e->getMessage() . '. Try converting to images instead.');
                }
            } else {
                // Image file - use vision API
                $imageBase64 = base64_encode(file_get_contents($file->getRealPath()));
                $imageData = [
                    'uri' => 'data:' . $fileMimeType . ';base64,' . $imageBase64,
                    'type' => $fileMimeType,
                    'fileName' => $file->getClientOriginalName(),
                ];

                // Create prompt for image-based quiz generation
                $prompt = $this->getImageQuizPrompt($quizType, $questionCount, $difficulty);
            }

            // Get AI response
            $aiResult = $this->unifiedAIService->chat(
                message: $prompt,
                modelId: $quizModelId,
                conversationHistory: [],
                streamCallback: null,
                imageData: $imageData
            );

            if (!$aiResult['success']) {
                throw new \Exception($aiResult['error'] ?? 'Failed to generate quiz');
            }

            // Parse quiz data from AI response
            $quizData = $this->parseQuizResponse($aiResult['content'], $quizType);

            // Check if any questions were found
            if (empty($quizData['questions']) || count($quizData['questions']) === 0) {
                \Log::error('No questions found in parsed response', [
                    'user_id' => $user->id,
                    'quiz_type' => $quizType,
                    'file_name' => $file->getClientOriginalName(),
                    'ai_response_length' => strlen($aiResult['content']),
                ]);

                throw new \Exception('No questions could be generated from this file. Please ensure the file contains clear, readable educational content. Try using a different file or check if the PDF text is readable.');
            }

            // Store in cache for future use
            $cachedQuiz = QuizCache::create([
                'image_hash' => $imageHash,
                'quiz_data' => $quizData,
                'quiz_type' => $quizType,
                'difficulty' => $difficulty,
                'subject' => $quizData['subject'] ?? null,
                'topics' => $quizData['topics'] ?? null,
                'question_count' => count($quizData['questions']),
                'usage_count' => 1,
                'last_used_at' => now(),
            ]);

            \Log::info('Quiz generated and cached', [
                'user_id' => $user->id,
                'quiz_cache_id' => $cachedQuiz->id,
                'question_count' => count($quizData['questions']),
            ]);

            return response()->json([
                'success' => true,
                'cached' => false,
                'book_mode' => $bookMode,
                'pdf_available' => true,
                'quiz' => [
                    'id' => $cachedQuiz->id,
                    'title' => $bookMode ? 'Quiz from Book' : 'Quiz from Image',
                    'description' => $bookMode ? 'Generated from uploaded book/document' : 'Generated from uploaded image',
                    'questions' => $quizData['questions'],
                    'total_questions' => count($quizData['questions']),
                    'difficulty' => $difficulty,
                    'quiz_type' => $quizType,
                    'subject' => $quizData['subject'] ?? null,
                    'topics' => $quizData['topics'] ?? null,
                ],
                'pdf_download' => [
                    'endpoint' => '/api/quiz/download-pdf',
                    'params' => [
                        'quiz_cache_id' => $cachedQuiz->id,
                        'show_answers' => true,
                        'show_explanations' => true,
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Quiz generation from image failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate quiz: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate prompt for image-based quiz
     * STRICTLY focuses on image content only
     */
    private function getImageQuizPrompt(string $quizType, int $questionCount, int $difficulty): string
    {
        $difficultyText = match($difficulty) {
            1 => 'very easy',
            2 => 'easy',
            3 => 'medium',
            4 => 'hard',
            5 => 'very challenging',
            default => 'medium'
        };

        $typeInstructions = match($quizType) {
            'mcq' => 'multiple choice questions (MCQs) with 4 options each (A, B, C, D) and indicate the correct answer',
            'true_false' => 'true/false questions with correct answers',
            'short_answer' => 'short answer questions',
            default => 'multiple choice questions (MCQs) with 4 options each'
        };

        $exampleQuestion = $quizType === 'mcq'
            ? '{
      "question": "What is the capital of France?",
      "options": {"A": "London", "B": "Paris", "C": "Berlin", "D": "Madrid"},
      "correct_answer": "B",
      "explanation": "Paris is the capital and largest city of France."
    }'
            : '{
      "question": "The Earth is flat.",
      "options": null,
      "correct_answer": "False",
      "explanation": "The Earth is an oblate spheroid, not flat."
    }';

        return "You are an expert educational quiz creator. Analyze the provided image and generate EXACTLY {$questionCount} {$typeInstructions}.

⚠️ STRICT CONTENT RULE - ABSOLUTELY MANDATORY:
- You MUST generate questions ONLY and STRICTLY from what is visible in the uploaded image
- DO NOT use any external knowledge, general knowledge, or information not present in the image
- DO NOT make up or assume any information that is not explicitly shown in the image
- If the image contains a math problem, ask about THAT specific problem only
- If the image contains text/notes, ask about THAT specific content only
- If the image contains a diagram, ask about THAT specific diagram only
- Every question, answer, and explanation MUST be directly derived from the image content
- If you cannot generate {$questionCount} questions from the image content alone, generate as many as possible from the actual content

CRITICAL INSTRUCTIONS:
1. You MUST respond with ONLY valid JSON - no other text before or after
2. Use STRICTLY ONLY the content visible in the image - ZERO external knowledge allowed
3. Generate exactly {$questionCount} questions (or less if image has limited content)
4. Focus on what is shown in the image (text, diagrams, formulas, charts, equations, etc.)
5. Difficulty level: {$difficultyText}
6. For MCQs: Each question MUST have exactly 4 options (A, B, C, D)
7. For True/False: Set options to null and correct_answer to \"True\" or \"False\"
8. For Short Answer: Set options to null and provide a brief answer
9. All explanations must reference the image content directly

JSON FORMAT (respond with ONLY this structure, no other text):
{
  \"subject\": \"[subject from image]\",
  \"topics\": [\"topic from image\"],
  \"questions\": [
    {$exampleQuestion}
  ]
}

IMPORTANT: Your response must start with { and end with }. Include exactly {$questionCount} questions in the questions array. Do not include any explanation or text outside the JSON structure. Remember: ONLY use content from the image, nothing else.";
    }

    /**
     * Generate prompt for text-based quiz (from PDF)
     */
    private function getTextQuizPrompt(string $text, string $quizType, int $questionCount, int $difficulty): string
    {
        $difficultyText = match($difficulty) {
            1 => 'very easy',
            2 => 'easy',
            3 => 'medium',
            4 => 'hard',
            5 => 'very challenging',
            default => 'medium'
        };

        $typeInstructions = match($quizType) {
            'mcq' => 'multiple choice questions (MCQs) with 4 options each (A, B, C, D) and indicate the correct answer',
            'true_false' => 'true/false questions with correct answers',
            'short_answer' => 'short answer questions',
            default => 'multiple choice questions (MCQs) with 4 options each'
        };

        // Limit text length to avoid token limits (approximately 3000 words)
        $maxChars = 15000;
        if (strlen($text) > $maxChars) {
            $text = substr($text, 0, $maxChars) . '...';
        }

        $exampleQuestion = $quizType === 'mcq'
            ? '{
      "question": "What is the capital of France?",
      "options": {"A": "London", "B": "Paris", "C": "Berlin", "D": "Madrid"},
      "correct_answer": "B",
      "explanation": "Paris is the capital and largest city of France."
    }'
            : '{
      "question": "The Earth is flat.",
      "options": null,
      "correct_answer": "False",
      "explanation": "The Earth is an oblate spheroid, not flat."
    }';

        return "You are an expert educational quiz creator. Based on the following text content, generate EXACTLY {$questionCount} {$typeInstructions}.

TEXT CONTENT FROM UPLOADED DOCUMENT:
{$text}

⚠️ STRICT CONTENT RULE - ABSOLUTELY MANDATORY:
- You MUST generate questions ONLY and STRICTLY from the text content provided above
- DO NOT use any external knowledge, general knowledge, or information not present in the document
- DO NOT make up or assume any information that is not explicitly written in the text above
- Every question MUST be directly answerable from the provided text content
- Every answer and explanation MUST be derived from the provided text content only
- If the text discusses specific concepts, formulas, or facts - ask about THOSE specific items only
- If you cannot generate {$questionCount} questions from the text alone, generate as many as possible from the actual content
- DO NOT add any information that a student couldn't find in the original document

CRITICAL INSTRUCTIONS:
1. You MUST respond with ONLY valid JSON - no other text before or after
2. Use STRICTLY ONLY the content from the text above - ZERO external knowledge allowed
3. Generate exactly {$questionCount} questions (or less if document has limited content)
4. Difficulty level: {$difficultyText}
5. For MCQs: Each question MUST have exactly 4 options (A, B, C, D)
6. For True/False: Set options to null and correct_answer to \"True\" or \"False\"
7. For Short Answer: Set options to null and provide a brief answer
8. All explanations must quote or reference the document content directly

JSON FORMAT (respond with ONLY this structure, no other text):
{
  \"subject\": \"[subject from document]\",
  \"topics\": [\"topics from document\"],
  \"questions\": [
    {$exampleQuestion}
  ]
}

IMPORTANT: Your response must start with { and end with }. Include exactly {$questionCount} questions in the questions array. Do not include any explanation or text outside the JSON structure. Remember: ONLY use content from the uploaded document, absolutely nothing else.";
    }

    /**
     * Parse AI response and extract quiz data
     * Robust parsing with multiple fallback strategies
     */
    private function parseQuizResponse(string $response, string $quizType): array
    {
        // Step 1: Clean and sanitize the response - fix UTF-8 encoding issues
        $response = $this->sanitizeUtf8($response);

        // If response is empty after sanitization, throw error
        if (empty(trim($response))) {
            \Log::error('Empty response after UTF-8 sanitization');
            throw new \Exception('AI returned empty response. Please try again.');
        }

        \Log::info('Parsing quiz response', [
            'response_length' => strlen($response),
            'quiz_type' => $quizType,
            'response_start' => mb_substr($response, 0, 200, 'UTF-8'),
        ]);

        // Step 2: Try multiple JSON extraction strategies
        $jsonParseAttempts = [
            // Attempt 1: Standard regex
            function($resp) {
                if (preg_match('/\{[\s\S]*\}/s', $resp, $matches)) {
                    return $matches[0];
                }
                return null;
            },
            // Attempt 2: Find balanced braces
            function($resp) {
                $start = strpos($resp, '{');
                if ($start === false) return null;

                $depth = 0;
                $length = strlen($resp);
                for ($i = $start; $i < $length; $i++) {
                    if ($resp[$i] === '{') $depth++;
                    elseif ($resp[$i] === '}') {
                        $depth--;
                        if ($depth === 0) {
                            return substr($resp, $start, $i - $start + 1);
                        }
                    }
                }
                return null;
            },
            // Attempt 3: Look for questions array directly
            function($resp) {
                if (preg_match('/\{"questions"\s*:\s*\[[\s\S]*?\]\s*\}/s', $resp, $matches)) {
                    return $matches[0];
                }
                return null;
            },
        ];

        $quizData = null;
        $lastError = '';

        foreach ($jsonParseAttempts as $attemptIndex => $extractFunc) {
            $jsonStr = $extractFunc($response);
            if ($jsonStr === null) continue;

            // Clean the JSON string
            $jsonStr = $this->cleanJsonString($jsonStr);

            // Try to decode
            $quizData = @json_decode($jsonStr, true);
            $lastError = json_last_error_msg();

            if (json_last_error() === JSON_ERROR_NONE && isset($quizData['questions'])) {
                \Log::info("JSON parsed successfully with attempt {$attemptIndex}");
                break;
            }

            // Try with JSON_INVALID_UTF8_SUBSTITUTE flag (PHP 7.2+)
            $quizData = @json_decode($jsonStr, true, 512, JSON_INVALID_UTF8_SUBSTITUTE);
            $lastError = json_last_error_msg();

            if (json_last_error() === JSON_ERROR_NONE && isset($quizData['questions'])) {
                \Log::info("JSON parsed with UTF8 substitution on attempt {$attemptIndex}");
                break;
            }

            $quizData = null;
        }

        // Step 3: If JSON parsing succeeded, validate and normalize questions
        if ($quizData !== null && isset($quizData['questions']) && is_array($quizData['questions'])) {
            $validatedQuestions = [];
            foreach ($quizData['questions'] as $index => $question) {
                // Ensure required fields exist
                if (!isset($question['question']) || empty(trim($question['question']))) {
                    \Log::warning("Question {$index} missing question text", ['question' => $question]);
                    continue;
                }

                // Normalize structure - sanitize each field
                $normalizedQuestion = [
                    'question' => $this->sanitizeUtf8((string)($question['question'] ?? '')),
                    'options' => null,
                    'correct_answer' => $this->sanitizeUtf8((string)($question['correct_answer'] ?? '')),
                    'explanation' => $this->sanitizeUtf8((string)($question['explanation'] ?? '')),
                ];

                // Handle options
                if (isset($question['options']) && is_array($question['options'])) {
                    $normalizedQuestion['options'] = [];
                    foreach ($question['options'] as $key => $value) {
                        $normalizedQuestion['options'][$key] = $this->sanitizeUtf8((string)$value);
                    }
                }

                // For MCQ, ensure options exist and are properly formatted
                if ($quizType === 'mcq') {
                    if (!is_array($normalizedQuestion['options']) || empty($normalizedQuestion['options'])) {
                        \Log::warning("MCQ question {$index} missing options", ['question' => $question]);
                        continue;
                    }

                    // Ensure we have A, B, C, D keys
                    $requiredKeys = ['A', 'B', 'C', 'D'];
                    foreach ($requiredKeys as $key) {
                        if (!isset($normalizedQuestion['options'][$key])) {
                            $normalizedQuestion['options'][$key] = '';
                        }
                    }
                }

                $validatedQuestions[] = $normalizedQuestion;
            }

            if (!empty($validatedQuestions)) {
                \Log::info('Quiz parsed successfully', [
                    'total_questions' => count($validatedQuestions),
                    'subject' => $quizData['subject'] ?? 'Unknown',
                ]);

                return [
                    'subject' => $this->sanitizeUtf8((string)($quizData['subject'] ?? 'General')),
                    'topics' => $quizData['topics'] ?? [],
                    'questions' => $validatedQuestions,
                ];
            }
        }

        \Log::warning('JSON parsing failed, trying fallback text parser', [
            'json_error' => $lastError,
            'response_preview' => mb_substr($response, 0, 500, 'UTF-8'),
        ]);

        // Step 4: Fallback to text-based parsing
        $textParsed = $this->parseTextQuizResponse($response, $quizType);

        if (empty($textParsed['questions'])) {
            \Log::error('All parsing methods failed', [
                'response_length' => strlen($response),
                'json_error' => $lastError,
            ]);
            throw new \Exception('AI returned invalid quiz format. Please try again.');
        }

        return $textParsed;
    }

    /**
     * Fallback parser for text-based quiz responses
     */
    private function parseTextQuizResponse(string $response, string $quizType): array
    {
        $questions = [];
        $lines = explode("\n", $response);
        $currentQuestion = null;
        $questionNum = 0;

        foreach ($lines as $line) {
            $line = trim($line);

            // Detect question start
            if (preg_match('/^(question|q)[\s\d]*\./i', $line)) {
                if ($currentQuestion) {
                    $questions[] = $currentQuestion;
                }
                $questionNum++;
                $currentQuestion = [
                    'id' => $questionNum,
                    'question' => preg_replace('/^(question|q)[\s\d]*\.\s*/i', '', $line),
                    'options' => $quizType === 'mcq' ? ['A' => '', 'B' => '', 'C' => '', 'D' => ''] : null,
                    'correct_answer' => '',
                    'explanation' => '',
                ];
            }
            // Detect options for MCQ
            elseif ($currentQuestion && $quizType === 'mcq' && preg_match('/^([A-D])[\)\.\s]+(.+)$/', $line, $matches)) {
                $option = $matches[1];
                $text = $matches[2];
                if ($currentQuestion['options'] !== null) {
                    $currentQuestion['options'][$option] = $text;
                }
            }
            // Detect correct answer
            elseif ($currentQuestion && preg_match('/^(correct\s*answer|answer)[\:\s]+(.+)$/i', $line, $matches)) {
                $currentQuestion['correct_answer'] = trim($matches[2]);
            }
            // Detect explanation
            elseif ($currentQuestion && preg_match('/^(explanation|solution)[\:\s]+(.+)$/i', $line, $matches)) {
                $currentQuestion['explanation'] = trim($matches[2]);
            }
        }

        if ($currentQuestion) {
            $questions[] = $currentQuestion;
        }

        return [
            'subject' => 'General',
            'topics' => ['Image Analysis'],
            'questions' => $questions,
        ];
    }

    /**
     * Sanitize UTF-8 string - removes invalid characters
     * Comprehensive fix for encoding issues from AI responses
     */
    private function sanitizeUtf8(string $string): string
    {
        // Step 1: Remove BOM if present
        $string = preg_replace('/^\xEF\xBB\xBF/', '', $string);

        // Step 2: Remove null bytes first
        $string = str_replace("\0", '', $string);

        // Step 3: Try to detect and fix encoding issues
        $encoding = mb_detect_encoding($string, ['UTF-8', 'ISO-8859-1', 'Windows-1252', 'ASCII'], true);

        if ($encoding === false || $encoding !== 'UTF-8') {
            // Try multiple encodings
            $encodings = ['UTF-8', 'ISO-8859-1', 'Windows-1252', 'ASCII', 'UTF-16', 'UTF-16LE', 'UTF-16BE'];
            foreach ($encodings as $enc) {
                $converted = @mb_convert_encoding($string, 'UTF-8', $enc);
                if ($converted !== false && mb_check_encoding($converted, 'UTF-8')) {
                    $string = $converted;
                    break;
                }
            }
        }

        // Step 4: Use iconv to remove invalid UTF-8 sequences (most reliable method)
        $string = @iconv('UTF-8', 'UTF-8//IGNORE', $string);
        if ($string === false) {
            $string = '';
        }

        // Step 5: Remove control characters (except newline, tab, carriage return)
        $string = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $string);

        // Step 6: Replace problematic Unicode characters that break JSON
        $string = preg_replace('/[\x{FFFE}\x{FFFF}]/u', '', $string);

        // Step 7: Normalize Unicode (NFC form)
        if (function_exists('normalizer_normalize')) {
            $normalized = \Normalizer::normalize($string, \Normalizer::FORM_C);
            if ($normalized !== false) {
                $string = $normalized;
            }
        }

        // Step 8: Final validation - if still invalid, try character-by-character rebuild
        if (!mb_check_encoding($string, 'UTF-8')) {
            $clean = '';
            $length = strlen($string);
            for ($i = 0; $i < $length; $i++) {
                $char = $string[$i];
                if (ord($char) < 128) {
                    $clean .= $char;
                } elseif (ord($char) >= 192 && ord($char) < 224 && $i + 1 < $length) {
                    $clean .= $string[$i] . $string[$i + 1];
                    $i++;
                } elseif (ord($char) >= 224 && ord($char) < 240 && $i + 2 < $length) {
                    $clean .= $string[$i] . $string[$i + 1] . $string[$i + 2];
                    $i += 2;
                } elseif (ord($char) >= 240 && $i + 3 < $length) {
                    $clean .= $string[$i] . $string[$i + 1] . $string[$i + 2] . $string[$i + 3];
                    $i += 3;
                }
                // Skip invalid bytes
            }
            $string = $clean;
        }

        return $string;
    }

    /**
     * Clean JSON string for proper parsing
     * Handles various malformed JSON from AI responses
     */
    private function cleanJsonString(string $json): string
    {
        // Step 1: Remove markdown code blocks if present
        $json = preg_replace('/^```(?:json)?\s*/i', '', $json);
        $json = preg_replace('/\s*```$/i', '', $json);

        // Step 2: Remove any text before first { and after last }
        $firstBrace = strpos($json, '{');
        $lastBrace = strrpos($json, '}');

        if ($firstBrace !== false && $lastBrace !== false && $lastBrace > $firstBrace) {
            $json = substr($json, $firstBrace, $lastBrace - $firstBrace + 1);
        }

        // Step 3: Fix common JSON issues

        // Remove trailing commas before } or ]
        $json = preg_replace('/,(\s*[}\]])/s', '$1', $json);

        // Fix unescaped newlines inside strings (common issue)
        // Replace actual newlines with escaped newlines, but only inside string values
        $json = preg_replace_callback('/"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"/s', function ($matches) {
            $value = $matches[1];
            // Escape unescaped newlines
            $value = str_replace(["\r\n", "\r", "\n"], "\\n", $value);
            // Escape unescaped tabs
            $value = str_replace("\t", "\\t", $value);
            return '"' . $value . '"';
        }, $json);

        // Step 4: Fix control characters that break JSON (except those we just escaped)
        $json = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $json);

        // Step 5: Fix common typos in JSON
        // Fix "correct_answer": A -> "correct_answer": "A"
        $json = preg_replace('/"correct_answer"\s*:\s*([A-D])(\s*[,}])/i', '"correct_answer": "$1"$2', $json);

        // Step 6: Fix unquoted property values that should be strings
        $json = preg_replace('/:\s*([A-Za-z][A-Za-z0-9_]*)\s*([,}])/i', ': "$1"$2', $json);

        // Step 7: Ensure proper encoding of special characters
        $json = preg_replace_callback('/[\x{0080}-\x{FFFF}]/u', function ($matches) {
            $char = $matches[0];
            // Keep valid UTF-8 characters, they're fine in JSON
            if (mb_check_encoding($char, 'UTF-8')) {
                return $char;
            }
            // Convert to unicode escape for invalid ones
            return sprintf('\\u%04x', mb_ord($char, 'UTF-8'));
        }, $json);

        return trim($json);
    }

    /**
     * Generate quiz by topic (without image)
     * Uses AI to generate quiz questions based on topic, subject, and difficulty
     */
    public function generateByTopic(Request $request)
    {
        $request->validate([
            'topic' => 'required|string|max:255',
            'subject' => 'sometimes|string|max:255',
            'exam_type' => 'sometimes|string|max:100',
            'difficulty' => 'sometimes|string|in:easy,medium,hard',
            'question_count' => 'sometimes|integer|min:5|max:100',
            'duration' => 'sometimes|integer|min:5|max:180',
            'language' => 'sometimes|string|in:english,hindi',
            'year' => 'sometimes|nullable|string|max:20', // Optional year filter (e.g., "2023", "2020-2023")
        ]);

        try {
            $user = Auth::user();
            $topic = $request->input('topic');
            $subject = $request->input('subject');
            $examType = $request->input('exam_type');
            $difficulty = $request->input('difficulty', 'medium');
            $duration = $request->input('duration', 15);
            $language = $request->input('language', 'english');
            $year = $request->input('year'); // Optional year filter

            // Calculate question count based on duration and difficulty if not provided
            $questionCount = $request->input('question_count');
            if (!$questionCount) {
                $questionCount = $this->calculateQuestionCount($duration, $difficulty);
            }

            \Log::info('Generating quiz by topic', [
                'user_id' => $user->id,
                'topic' => $topic,
                'subject' => $subject,
                'exam_type' => $examType,
                'difficulty' => $difficulty,
                'question_count' => $questionCount,
                'duration' => $duration,
                'language' => $language,
                'year' => $year,
            ]);

            // Use StudentDoubtSolverService to generate quiz
            $doubtSolverService = new \App\Services\StudentDoubtSolverService();

            // Build context with exam type, language, and year if provided
            $topicWithContext = $topic;
            if ($examType) {
                $topicWithContext = "[{$examType}] {$topic}";
            }
            if ($year) {
                // Strict year filter - ONLY from selected year, not before or after
                if (strpos($year, '-') !== false) {
                    // Range like "2019-2023" - questions from this range only
                    $topicWithContext .= " [STRICT YEAR FILTER: Generate questions ONLY from years {$year}. DO NOT include questions from years before or after this range. Questions must be based on exam patterns and syllabus from these specific years only.]";
                } else {
                    // Single year like "2023" - questions from this year only
                    $topicWithContext .= " [STRICT YEAR FILTER: Generate questions ONLY from year {$year}. DO NOT include questions from any other year - not before {$year} and not after {$year}. Questions must match the exact exam pattern and syllabus of {$year}.]";
                }
            }
            if ($language && $language !== 'english') {
                $topicWithContext .= " [Language: {$language}]";
            }

            // Try quiz generation with retry logic
            $quizData = null;
            $lastError = null;
            $maxRetries = 2;

            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                try {
                    \Log::info("Quiz generation attempt {$attempt}/{$maxRetries}", [
                        'topic' => $topic,
                        'question_count' => $questionCount,
                    ]);

                    $quizResponse = $doubtSolverService->generateQuiz(
                        $topicWithContext,
                        $subject,
                        $questionCount,
                        $difficulty
                    );

                    // Parse the response to extract JSON
                    $quizData = $this->parseQuizResponse($quizResponse, 'mcq');

                    if ($quizData && !empty($quizData['questions'])) {
                        \Log::info("Quiz generation successful on attempt {$attempt}", [
                            'questions_count' => count($quizData['questions']),
                        ]);
                        break; // Success, exit retry loop
                    }

                    $lastError = 'Empty questions array';
                    \Log::warning("Quiz attempt {$attempt} returned empty data, retrying...", [
                        'response_preview' => substr($quizResponse, 0, 300),
                    ]);

                } catch (\Exception $e) {
                    $lastError = $e->getMessage();
                    \Log::warning("Quiz attempt {$attempt} failed: {$lastError}");

                    if ($attempt === $maxRetries) {
                        throw $e;
                    }
                }

                // Small delay before retry
                if ($attempt < $maxRetries) {
                    usleep(500000); // 0.5 second delay
                }
            }

            if (!$quizData || empty($quizData['questions'])) {
                \Log::error('Quiz generation failed after all retries', [
                    'last_error' => $lastError,
                    'topic' => $topic,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to generate quiz. Please try again with a different topic or settings.',
                    'error_details' => $lastError,
                ], 500);
            }

            // Add metadata to quiz data
            $quizData['topic'] = $topic;
            $quizData['subject'] = $subject;
            $quizData['exam_type'] = $examType;
            $quizData['difficulty'] = $difficulty;
            $quizData['duration'] = $duration;
            $quizData['language'] = $language;
            $quizData['question_count'] = count($quizData['questions']);

            return response()->json([
                'success' => true,
                'quiz' => [
                    'title' => "Quiz: {$topic}",
                    'description' => $subject ? "Subject: {$subject}" : "Topic-based quiz",
                    'questions' => $quizData['questions'],
                    'total_questions' => count($quizData['questions']),
                    'difficulty' => $difficulty,
                    'duration' => $duration,
                    'language' => $language,
                    'topic' => $topic,
                    'subject' => $subject,
                    'exam_type' => $examType,
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Quiz by topic generation failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_type' => get_class($e),
            ], 500);
        }
    }

    /**
     * Generate reasoning quiz
     * Uses AI to generate reasoning/aptitude questions based on category and difficulty
     */
    public function generateReasoningQuiz(Request $request)
    {
        $request->validate([
            'category' => 'required|string|max:255',
            'difficulty' => 'required|string|in:easy,medium,hard',
            'language' => 'required|string|in:english,hindi',
            'duration' => 'required|integer|min:5|max:180',
            'question_count' => 'sometimes|integer|min:5|max:50',
        ]);

        try {
            $user = Auth::user();
            $category = $request->input('category');
            $difficulty = $request->input('difficulty');
            $language = $request->input('language');
            $duration = $request->input('duration');

            // Calculate question count based on duration and difficulty if not provided
            $questionCount = $request->input('question_count');
            if (!$questionCount) {
                $questionCount = $this->calculateQuestionCount($duration, $difficulty);
            }

            \Log::info('Generating reasoning quiz', [
                'user_id' => $user->id,
                'category' => $category,
                'difficulty' => $difficulty,
                'question_count' => $questionCount,
                'duration' => $duration,
                'language' => $language,
            ]);

            // Use StudentDoubtSolverService to generate quiz
            $doubtSolverService = new \App\Services\StudentDoubtSolverService();

            // Build topic with reasoning context
            $topicWithContext = "Reasoning and Aptitude - {$category}";
            if ($language !== 'english') {
                $topicWithContext .= " [Language: {$language}]";
            }

            // Add reasoning-specific instructions
            $topicWithContext .= " [TYPE: Logical Reasoning, Aptitude, Problem Solving. Include pattern recognition, series completion, analogy, coding-decoding, blood relations, direction sense, seating arrangement, syllogism, and other reasoning types as applicable to the category.]";

            // Try quiz generation with retry logic
            $quizData = null;
            $lastError = null;
            $maxRetries = 2;

            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                try {
                    \Log::info("Reasoning quiz generation attempt {$attempt}/{$maxRetries}", [
                        'category' => $category,
                        'question_count' => $questionCount,
                    ]);

                    $quizResponse = $doubtSolverService->generateQuiz(
                        $topicWithContext,
                        'Reasoning & Aptitude',
                        $questionCount,
                        $difficulty
                    );

                    // Parse the response to extract JSON
                    $quizData = $this->parseQuizResponse($quizResponse, 'mcq');

                    if ($quizData && !empty($quizData['questions'])) {
                        \Log::info("Reasoning quiz generation successful on attempt {$attempt}", [
                            'questions_count' => count($quizData['questions']),
                        ]);
                        break;
                    }

                    $lastError = 'Empty questions array';
                    \Log::warning("Reasoning quiz attempt {$attempt} returned empty data, retrying...");

                } catch (\Exception $e) {
                    $lastError = $e->getMessage();
                    \Log::warning("Reasoning quiz attempt {$attempt} failed: {$lastError}");

                    if ($attempt === $maxRetries) {
                        throw $e;
                    }
                }

                if ($attempt < $maxRetries) {
                    usleep(500000);
                }
            }

            if (!$quizData || empty($quizData['questions'])) {
                \Log::error('Reasoning quiz generation failed after all retries', [
                    'last_error' => $lastError,
                    'category' => $category,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to generate reasoning quiz. Please try again.',
                    'error_details' => $lastError,
                ], 500);
            }

            return response()->json([
                'success' => true,
                'quiz' => [
                    'title' => "Reasoning Quiz: {$category}",
                    'description' => "Reasoning & Aptitude - {$difficulty} level",
                    'questions' => $quizData['questions'],
                    'total_questions' => count($quizData['questions']),
                    'difficulty' => $difficulty,
                    'duration' => $duration,
                    'language' => $language,
                    'category' => $category,
                    'type' => 'reasoning',
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Reasoning quiz generation failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_type' => get_class($e),
            ], 500);
        }
    }

    /**
     * Download quiz as PDF
     * Can be used for both cached quizzes and on-the-fly generation
     */
    public function downloadQuizPdf(Request $request)
    {
        $request->validate([
            'quiz_cache_id' => 'required|integer|exists:quiz_cache,id',
            'show_answers' => 'sometimes|boolean',
            'show_explanations' => 'sometimes|boolean',
        ]);

        try {
            $user = Auth::user();
            $quizCacheId = $request->input('quiz_cache_id');
            $showAnswers = $request->input('show_answers', true);
            $showExplanations = $request->input('show_explanations', true);

            // Get quiz from cache
            $cachedQuiz = QuizCache::findOrFail($quizCacheId);

            // Prepare quiz data
            $quizData = $cachedQuiz->quiz_data;

            // PDF options
            $options = [
                'quiz_type' => $cachedQuiz->quiz_type,
                'difficulty' => $cachedQuiz->difficulty,
                'show_answers' => $showAnswers,
                'show_explanations' => $showExplanations,
            ];

            \Log::info('Generating quiz PDF', [
                'user_id' => $user->id,
                'quiz_cache_id' => $quizCacheId,
                'show_answers' => $showAnswers,
                'show_explanations' => $showExplanations,
            ]);

            // Generate and download PDF
            $subject = $quizData['subject'] ?? 'Quiz';
            $filename = str_replace(' ', '_', $subject) . '_' . now()->format('Y-m-d_His') . '.pdf';

            return $this->pdfGenerator->downloadQuizPdf($quizData, $options, $filename);

        } catch (\Exception $e) {
            \Log::error('Quiz PDF download failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download answer key PDF
     */
    public function downloadAnswerKeyPdf(Request $request)
    {
        $request->validate([
            'quiz_cache_id' => 'required|integer|exists:quiz_cache,id',
        ]);

        try {
            $user = Auth::user();
            $quizCacheId = $request->input('quiz_cache_id');

            // Get quiz from cache
            $cachedQuiz = QuizCache::findOrFail($quizCacheId);

            // Prepare quiz data
            $quizData = $cachedQuiz->quiz_data;

            \Log::info('Generating answer key PDF', [
                'user_id' => $user->id,
                'quiz_cache_id' => $quizCacheId,
            ]);

            // Generate and download answer key PDF
            $subject = $quizData['subject'] ?? 'Quiz';
            $filename = str_replace(' ', '_', $subject) . '_Answer_Key_' . now()->format('Y-m-d') . '.pdf';

            $pdf = $this->pdfGenerator->generateAnswerKeyPdf($quizData);
            return $pdf->download($filename);

        } catch (\Exception $e) {
            \Log::error('Answer key PDF download failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate answer key PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate optimal question count based on duration and difficulty
     *
     * Formula:
     * - Base: 1 question per 1.5-2 minutes
     * - Easy: More questions (faster to solve) - 1 question per 1.5 min
     * - Medium: Normal pace - 1 question per 2 min
     * - Hard: Fewer questions (need more time) - 1 question per 2.5 min
     *
     * @param int $durationMinutes Duration in minutes (5-180)
     * @param string $difficulty Difficulty level (easy, medium, hard)
     * @return int Calculated question count
     */
    private function calculateQuestionCount(int $durationMinutes, string $difficulty): int
    {
        // Time per question based on difficulty (in minutes)
        $timePerQuestion = match ($difficulty) {
            'easy' => 1.5,      // Easy questions: 1.5 min each
            'medium' => 2.0,    // Medium questions: 2 min each
            'hard' => 2.5,      // Hard questions: 2.5 min each
            default => 2.0,     // Default to medium
        };

        // Calculate base question count
        $questionCount = (int) floor($durationMinutes / $timePerQuestion);

        // Apply min/max limits based on duration
        if ($durationMinutes <= 10) {
            // Short quiz: 5-10 questions
            return max(5, min($questionCount, 10));
        } elseif ($durationMinutes <= 30) {
            // Medium quiz: 10-20 questions
            return max(10, min($questionCount, 20));
        } elseif ($durationMinutes <= 60) {
            // Long quiz: 15-40 questions
            return max(15, min($questionCount, 40));
        } else {
            // Very long quiz (1+ hour): 30-100 questions
            return max(30, min($questionCount, 100));
        }
    }
}
