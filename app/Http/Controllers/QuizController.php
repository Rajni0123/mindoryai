<?php

namespace App\Http\Controllers;

use App\Models\Notebook;
use App\Models\Quiz;
use App\Models\QuizCache;
use App\Support\ResourceAuthorizer;
use App\Services\UnifiedAIService;
use App\Services\QuizPdfGenerator;
use App\Services\StudentDoubtSolverService;
use App\Services\UsageLimitService;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class QuizController extends Controller
{
    private UnifiedAIService $unifiedAIService;
    private QuizPdfGenerator $pdfGenerator;
    private UsageLimitService $usageLimitService;

    public function __construct(
        UnifiedAIService $unifiedAIService,
        QuizPdfGenerator $pdfGenerator,
        UsageLimitService $usageLimitService
    )
    {
        $this->unifiedAIService = $unifiedAIService;
        $this->pdfGenerator = $pdfGenerator;
        $this->usageLimitService = $usageLimitService;
    }

    /**
     * Generate quiz from chat interface
     * Can generate from notes (notebook) or from current chat context
     */
    public function generateFromChat(Request $request)
    {
        $request->validate([
            'source' => 'required|in:notes,chat',
            'question_count' => 'sometimes|integer|min:3|max:50',  // Min 3 questions
            'difficulty' => 'sometimes|in:easy,medium,hard',
            'notebook_id' => 'sometimes|integer|exists:notebooks,id',
        ]);

        // Check usage limits
        $user = Auth::user();
        if ($user) {
            $limitCheck = $this->usageLimitService->canUse($user, 'video_quiz');
            if (!$limitCheck['allowed']) {
                return response()->json([
                    'success' => false,
                    'message' => $limitCheck['reason'],
                    'limit_reached' => true,
                    'used' => $limitCheck['used'],
                    'limit' => $limitCheck['limit'],
                ], 429);
            }
        }

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

                // Record usage after successful quiz generation
                if ($user) {
                    $this->usageLimitService->recordUsage($user, 'video_quiz');
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

                // Record usage after successful quiz generation
                if ($user) {
                    $this->usageLimitService->recordUsage($user, 'video_quiz');
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

            // Check usage limits
            if ($user) {
                $limitCheck = $this->usageLimitService->canUse($user, 'video_quiz');
                if (!$limitCheck['allowed']) {
                    return response()->json([
                        'success' => false,
                        'message' => $limitCheck['reason'],
                        'limit_reached' => true,
                        'used' => $limitCheck['used'],
                        'limit' => $limitCheck['limit'],
                    ], 429);
                }
            }
            $file = $request->file('image');
            $quizType = $request->input('quiz_type', 'mcq');
            $questionCount = $request->input('question_count', 5);
            $difficulty = $request->input('difficulty', 2);
            $bookMode = $request->input('book_mode', false);
            $generatePdf = $request->input('generate_pdf', false);

            // Credits removed — access controlled by plan limits

            \Log::info('Generating new quiz from image', [
                'user_id' => $user->id,
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

            // Store quiz result for PDF download
            $cachedQuiz = QuizCache::create([
                'user_id' => $user->id,
                'image_hash' => Str::uuid()->toString(),
                'quiz_data' => $quizData,
                'quiz_type' => $quizType,
                'difficulty' => $difficulty,
                'subject' => $quizData['subject'] ?? null,
                'topics' => $quizData['topics'] ?? null,
                'question_count' => count($quizData['questions']),
                'usage_count' => 1,
                'last_used_at' => now(),
            ]);

            \Log::info('Quiz generated and stored', [
                'user_id' => $user->id,
                'quiz_cache_id' => $cachedQuiz->id,
                'question_count' => count($quizData['questions']),
            ]);

            // Record usage after successful generation
            if ($user) {
                $this->usageLimitService->recordUsage($user, 'video_quiz');
            }

            return response()->json([
                'success' => true,
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
     * ULTRA-ROBUST parsing with multiple fallback strategies
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

        // QUICK CHECK: If response is already valid JSON, use it directly
        $directParse = @json_decode($response, true);
        if ($directParse !== null && isset($directParse['questions']) && !empty($directParse['questions'])) {
            \Log::info('Direct JSON parse successful', ['questions_count' => count($directParse['questions'])]);
            return $this->normalizeQuizData($directParse, $quizType);
        }

        // Step 1.4: Check for truncated JSON response
        // Truncated responses typically end mid-word or mid-sentence
        $trimmed = trim($response);
        $lastChar = substr($trimmed, -1);
        $endsWithValidJson = in_array($lastChar, ['}', ']', '"']);

        if (!$endsWithValidJson) {
            \Log::warning('Possible truncated response detected', [
                'last_50_chars' => substr($trimmed, -50),
                'last_char' => $lastChar,
            ]);

            // Try to repair truncated JSON by finding last complete question
            if (preg_match('/^(.*?"explanation"\s*:\s*"[^"]*")\s*\}[,\s]*\]/s', $trimmed, $repairMatch)) {
                $response = $repairMatch[1] . '}]}';
                \Log::info('Repaired truncated JSON by finding last complete question');
            } elseif (preg_match('/^(.*?\})\s*,?\s*$/s', $trimmed, $partialMatch)) {
                // Find last complete object and close the array
                if (preg_match_all('/\{[^{}]*"question"[^{}]*\}/s', $trimmed, $allQuestions)) {
                    $questions = $allQuestions[0];
                    if (!empty($questions)) {
                        $response = '{"questions":[' . implode(',', $questions) . ']}';
                        \Log::info('Reconstructed JSON from ' . count($questions) . ' complete questions');
                    }
                }
            }
        }

        // Step 1.5: Strip markdown code blocks if present
        // Handle ```json ... ``` or ``` ... ``` patterns
        if (preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/', $response, $codeBlockMatch)) {
            $response = trim($codeBlockMatch[1]);
            \Log::info('Extracted content from markdown code block');
        }

        // Step 1.6: Strip common AI preamble text before JSON
        // AI sometimes says "Here are your questions:" or "Sure! Here's the quiz:" before JSON
        $preamblePatterns = [
            '/^.*?(?=\{|\[)/s',  // Everything before first { or [
            '/^(?:Here(?:\'s| is| are).*?[:]\s*)/is',
            '/^(?:Sure[!,.]?\s*)/is',
            '/^(?:Of course[!,.]?\s*)/is',
            '/^(?:I\'ll generate.*?[:]\s*)/is',
            '/^(?:Below (?:are|is).*?[:]\s*)/is',
        ];

        $originalResponse = $response;
        $trimmedResponse = trim($response);

        // Only strip preamble if response doesn't start with { or [
        if (!preg_match('/^\s*[\{\[]/', $trimmedResponse)) {
            foreach ($preamblePatterns as $pattern) {
                $stripped = preg_replace($pattern, '', $trimmedResponse, 1);
                if ($stripped !== $trimmedResponse && preg_match('/^\s*[\{\[]/', $stripped)) {
                    $response = trim($stripped);
                    \Log::info('Stripped AI preamble text');
                    break;
                }
            }
        }

        \Log::info('Parsing quiz response', [
            'response_length' => strlen($response),
            'quiz_type' => $quizType,
            'response_start' => mb_substr($response, 0, 200, 'UTF-8'),
        ]);

        // Step 2: Try multiple JSON extraction strategies
        $jsonParseAttempts = [
            // Attempt 0: Direct array - AI returned [...] instead of {"questions": [...]}
            function($resp) {
                $trimmed = trim($resp);
                if (str_starts_with($trimmed, '[') && str_ends_with($trimmed, ']')) {
                    // Wrap array in questions object
                    return '{"questions":' . $trimmed . '}';
                }
                return null;
            },
            // Attempt 1: Standard regex for object
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
            // Attempt 4: Extract array and wrap it
            function($resp) {
                if (preg_match('/\[[\s\S]*\]/s', $resp, $matches)) {
                    return '{"questions":' . $matches[0] . '}';
                }
                return null;
            },
            // Attempt 5: Find balanced brackets for array
            function($resp) {
                $start = strpos($resp, '[');
                if ($start === false) return null;

                $depth = 0;
                $length = strlen($resp);
                for ($i = $start; $i < $length; $i++) {
                    if ($resp[$i] === '[') $depth++;
                    elseif ($resp[$i] === ']') {
                        $depth--;
                        if ($depth === 0) {
                            $arr = substr($resp, $start, $i - $start + 1);
                            return '{"questions":' . $arr . '}';
                        }
                    }
                }
                return null;
            },
            // Attempt 6: Handle line-by-line JSON objects (sometimes AI returns each question on a line)
            function($resp) {
                $questions = [];
                $lines = explode("\n", $resp);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (preg_match('/^\{.*"question".*\}[,]?$/', $line)) {
                        $cleanLine = rtrim($line, ',');
                        $parsed = @json_decode($cleanLine, true);
                        if ($parsed && isset($parsed['question'])) {
                            $questions[] = $parsed;
                        }
                    }
                }
                if (!empty($questions)) {
                    return json_encode(['questions' => $questions]);
                }
                return null;
            },
            // Attempt 7: Try to fix common JSON syntax errors
            function($resp) {
                // Find JSON object or array
                if (!preg_match('/[\{\[]/', $resp)) return null;

                $start = strpos($resp, '{');
                $arrStart = strpos($resp, '[');
                if ($start === false || ($arrStart !== false && $arrStart < $start)) {
                    $start = $arrStart;
                }
                if ($start === false) return null;

                $json = substr($resp, $start);

                // Fix common issues
                $json = preg_replace('/,\s*([}\]])/', '$1', $json); // Remove trailing commas
                $json = preg_replace('/([}\]])\s*([{\[])/s', '$1,$2', $json); // Add missing commas between objects
                $json = preg_replace('/\'([^\']*)\'/s', '"$1"', $json); // Replace single quotes with double quotes

                // If it's an array, wrap it
                if (str_starts_with(trim($json), '[')) {
                    $json = '{"questions":' . $json . '}';
                }

                return $json;
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
                $rawAnswer = $this->extractCorrectAnswerRaw($question);
                $normalizedQuestion = [
                    'question' => $this->sanitizeUtf8((string)($question['question'] ?? '')),
                    'options' => null,
                    'correct_answer' => $this->sanitizeUtf8((string)($rawAnswer ?? '')),
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

                    $correctIndex = $this->resolveMcqCorrectIndex(
                        $rawAnswer,
                        $normalizedQuestion['options']
                    );
                    $normalizedQuestion['correct_index'] = $correctIndex;
                    $normalizedQuestion['correct_answer'] = ['A', 'B', 'C', 'D'][$correctIndex] ?? 'A';
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
                'response_start' => mb_substr($response, 0, 200, 'UTF-8'),
                'response_end' => mb_substr($response, -200, 200, 'UTF-8'),
            ]);

            // Check if response appears to be truncated
            $trimmed = trim($response);
            if (!empty($trimmed) && !preg_match('/[}\]]$/s', $trimmed)) {
                throw new \Exception('Quiz generation incomplete - response was truncated. Please try again.');
            }

            // Check if response contains error message from AI
            if (preg_match('/(error|unable|cannot|sorry|apologize)/i', $response)) {
                throw new \Exception('AI was unable to generate quiz. Please try a different topic.');
            }

            // Generic error with context
            throw new \Exception('Could not parse quiz response. Please try again.');
        }

        return $textParsed;
    }

    /**
     * Normalize quiz data structure
     * Ensures consistent format for all quiz responses
     */
    private function normalizeQuizData(array $quizData, string $quizType): array
    {
        $validatedQuestions = [];

        foreach ($quizData['questions'] as $index => $question) {
            if (!isset($question['question']) || empty(trim($question['question']))) {
                continue;
            }

            $rawAnswer = $this->extractCorrectAnswerRaw($question);
            $normalizedQuestion = [
                'question' => $this->sanitizeUtf8((string)($question['question'] ?? '')),
                'options' => null,
                'correct_answer' => $this->sanitizeUtf8((string)($rawAnswer ?? '')),
                'explanation' => $this->sanitizeUtf8((string)($question['explanation'] ?? '')),
            ];

            if (isset($question['options']) && is_array($question['options'])) {
                $normalizedQuestion['options'] = [];
                foreach ($question['options'] as $key => $value) {
                    $normalizedQuestion['options'][strtoupper($key)] = $this->sanitizeUtf8((string)$value);
                }
            }

            if ($quizType === 'mcq' && (!is_array($normalizedQuestion['options']) || empty($normalizedQuestion['options']))) {
                continue;
            }

            if ($quizType === 'mcq' && is_array($normalizedQuestion['options'])) {
                $correctIndex = $this->resolveMcqCorrectIndex(
                    $rawAnswer,
                    $normalizedQuestion['options']
                );
                $normalizedQuestion['correct_index'] = $correctIndex;
                $normalizedQuestion['correct_answer'] = ['A', 'B', 'C', 'D'][$correctIndex] ?? 'A';
            }

            $validatedQuestions[] = $normalizedQuestion;
        }

        return [
            'subject' => $this->sanitizeUtf8((string)($quizData['subject'] ?? 'General')),
            'topics' => $quizData['topics'] ?? [],
            'questions' => $validatedQuestions,
        ];
    }

    /**
     * Fallback parser for text-based quiz responses
     * Enhanced to handle various question formats
     */
    private function parseTextQuizResponse(string $response, string $quizType): array
    {
        $questions = [];
        $lines = explode("\n", $response);
        $currentQuestion = null;
        $questionNum = 0;

        // Additional patterns for question detection
        $questionPatterns = [
            '/^(question|q)\s*[\d]*[\.\:\)]/i',  // Question 1. or Q1:
            '/^\d+[\.\:\)]\s+/i',                 // 1. or 1: or 1)
            '/^[\*\-]\s*\**(question|q)/i',      // * Question or - **Question
            '/^##?\s*(question|q)/i',             // # Question or ## Q
        ];

        // Additional patterns for options
        $optionPatterns = [
            '/^([A-Da-d])[\)\.\:\s]+(.+)$/i',    // A) or a. or A:
            '/^[\*\-]\s*([A-Da-d])[\)\.\:\s]+(.+)$/i', // * A) or - A.
            '/^\(([A-Da-d])\)\s*(.+)$/i',        // (A) text
        ];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // Detect question start with multiple patterns
            $isQuestion = false;
            $questionText = $line;

            foreach ($questionPatterns as $pattern) {
                if (preg_match($pattern, $line)) {
                    $isQuestion = true;
                    // Clean the question text
                    $questionText = preg_replace('/^(question|q)\s*[\d]*[\.\:\)]\s*/i', '', $line);
                    $questionText = preg_replace('/^\d+[\.\:\)]\s*/', '', $questionText);
                    $questionText = preg_replace('/^[\*\-\#]+\s*/', '', $questionText);
                    $questionText = preg_replace('/^\*+|\*+$/', '', $questionText);
                    break;
                }
            }

            if ($isQuestion && !empty(trim($questionText))) {
                if ($currentQuestion && !empty($currentQuestion['question'])) {
                    $questions[] = $currentQuestion;
                }
                $questionNum++;
                $currentQuestion = [
                    'id' => $questionNum,
                    'question' => trim($questionText),
                    'options' => $quizType === 'mcq' ? ['A' => '', 'B' => '', 'C' => '', 'D' => ''] : null,
                    'correct_answer' => '',
                    'explanation' => '',
                ];
                continue;
            }

            // Detect options for MCQ using multiple patterns
            if ($currentQuestion && $quizType === 'mcq') {
                foreach ($optionPatterns as $pattern) {
                    if (preg_match($pattern, $line, $matches)) {
                        $option = strtoupper($matches[1]);
                        $text = trim($matches[2]);
                        if ($currentQuestion['options'] !== null && in_array($option, ['A', 'B', 'C', 'D'])) {
                            $currentQuestion['options'][$option] = $text;
                        }
                        break;
                    }
                }
            }

            // Detect correct answer with multiple patterns
            if ($currentQuestion && preg_match('/^(correct\s*answer|answer|ans|correct)[\:\s]+(.+)$/i', $line, $matches)) {
                $answer = trim($matches[2]);
                // Normalize to just A, B, C, D if it's a letter
                if (preg_match('/^([A-Da-d])[\)\.\s]?/i', $answer, $letterMatch)) {
                    $currentQuestion['correct_answer'] = strtoupper($letterMatch[1]);
                } else {
                    $currentQuestion['correct_answer'] = $answer;
                }
            }

            // Detect explanation
            if ($currentQuestion && preg_match('/^(explanation|solution|reason|why)[\:\s]+(.+)$/i', $line, $matches)) {
                $currentQuestion['explanation'] = trim($matches[2]);
            }
        }

        // Don't forget the last question
        if ($currentQuestion && !empty($currentQuestion['question'])) {
            $questions[] = $currentQuestion;
        }

        if ($quizType === 'mcq') {
            foreach ($questions as $idx => $question) {
                if (!is_array($question['options'] ?? null)) {
                    continue;
                }
                $correctIndex = $this->resolveMcqCorrectIndex(
                    $question['correct_answer'] ?? '',
                    $question['options']
                );
                $questions[$idx]['correct_index'] = $correctIndex;
                $questions[$idx]['correct_answer'] = ['A', 'B', 'C', 'D'][$correctIndex] ?? 'A';
            }
        }

        // Log result
        \Log::info('Text fallback parser result', [
            'questions_found' => count($questions),
        ]);

        return [
            'subject' => 'General',
            'topics' => ['Quiz'],
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

        // Step 2: Remove any text before first { or [ and after last } or ]
        $firstBrace = strpos($json, '{');
        $firstBracket = strpos($json, '[');
        $lastBrace = strrpos($json, '}');
        $lastBracket = strrpos($json, ']');

        // Determine the correct start and end
        $start = false;
        $end = false;
        $isArray = false;

        if ($firstBrace !== false && ($firstBracket === false || $firstBrace < $firstBracket)) {
            $start = $firstBrace;
            $end = $lastBrace;
        } elseif ($firstBracket !== false) {
            $start = $firstBracket;
            $end = $lastBracket;
            $isArray = true;
        }

        if ($start !== false && $end !== false && $end > $start) {
            $json = substr($json, $start, $end - $start + 1);
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
            'question_count' => 'sometimes|integer|min:3|max:50',  // Min 3, Max 50 for optimal generation
            'duration' => 'sometimes|integer|min:3|max:180',       // Min 3 minutes
            'language' => 'sometimes|string|in:english,hindi,hinglish',
            'year' => 'sometimes|nullable|string|max:20', // Optional year filter (e.g., "2023", "2020-2023")
        ]);

        try {
            $user = Auth::user();

            // Check usage limits
            if ($user) {
                $limitCheck = $this->usageLimitService->canUse($user, 'topic_quiz');
                if (!$limitCheck['allowed']) {
                    return response()->json([
                        'success' => false,
                        'message' => $limitCheck['reason'],
                        'limit_reached' => true,
                        'used' => $limitCheck['used'],
                        'limit' => $limitCheck['limit'],
                    ], 429);
                }
            }

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
                    $topicWithContext .= " [STRICT YEAR FILTER: Generate questions ONLY from years {$year}. DO NOT include questions from years before or after this range. Questions must be based on exam patterns and syllabus from these specific years only.]";
                } else {
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

            // Record usage after successful generation
            if ($user) {
                $this->usageLimitService->recordUsage($user, 'topic_quiz');
            }

            $quizResult = [
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
            ];

            return response()->json([
                'success' => true,
                'quiz' => $quizResult,
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
            'duration' => 'required|integer|min:3|max:180',  // Min 3 minutes
            'question_count' => 'sometimes|integer|min:3|max:50',  // Min 3 questions
        ]);

        // Check usage limits
        $user = Auth::user();
        if ($user) {
            $limitCheck = $this->usageLimitService->canUse($user, 'topic_quiz');
            if (!$limitCheck['allowed']) {
                return response()->json([
                    'success' => false,
                    'message' => $limitCheck['reason'],
                    'limit_reached' => true,
                    'used' => $limitCheck['used'],
                    'limit' => $limitCheck['limit'],
                ], 429);
            }
        }

        try {
            $category = $request->input('category');
            $difficulty = $request->input('difficulty');
            $language = $request->input('language');
            $duration = $request->input('duration');

            // Calculate question count based on duration and difficulty if not provided
            $questionCount = $request->input('question_count');
            if (!$questionCount) {
                $questionCount = $this->calculateQuestionCount($duration, $difficulty);
            }

            // Optimize question count for faster generation (max 15 for speed)
            $questionCount = min($questionCount, 15);

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

            // Build topic with reasoning context - simplified for better JSON output
            $topicWithContext = "Reasoning - {$category}";
            if ($language !== 'english') {
                $topicWithContext .= " [Language: {$language}]";
            }

            // Simplified reasoning instruction for cleaner JSON
            $topicWithContext .= " [TYPE: Logical Reasoning, Aptitude]";

            // Try quiz generation with retry logic
            $quizData = null;
            $lastError = null;
            $maxRetries = 3; // Increased retries for better success rate

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

                    // Log raw response for debugging
                    \Log::debug('Raw quiz response', [
                        'response_length' => strlen($quizResponse),
                        'response_preview' => substr($quizResponse, 0, 500),
                    ]);

                    // Parse the response to extract JSON
                    $quizData = $this->parseQuizResponse($quizResponse, 'mcq');

                    if ($quizData && !empty($quizData['questions'])) {
                        \Log::info("Reasoning quiz generation successful on attempt {$attempt}", [
                            'questions_count' => count($quizData['questions']),
                        ]);
                        break;
                    }

                    $lastError = 'Empty questions array - AI did not return valid questions';
                    \Log::warning("Reasoning quiz attempt {$attempt} returned empty data, retrying...", [
                        'response_preview' => substr($quizResponse, 0, 300),
                    ]);

                } catch (\Exception $e) {
                    $lastError = $e->getMessage();
                    \Log::warning("Reasoning quiz attempt {$attempt} failed: {$lastError}");

                    // Don't throw on last attempt - return friendly error instead
                    if ($attempt === $maxRetries) {
                        break;
                    }
                }

                // Delay before retry (increasing delay)
                if ($attempt < $maxRetries) {
                    usleep(500000 * $attempt); // 0.5s, 1s, 1.5s
                }
            }

            if (!$quizData || empty($quizData['questions'])) {
                \Log::error('Reasoning quiz generation failed after all retries', [
                    'last_error' => $lastError,
                    'category' => $category,
                ]);

                // User-friendly error message
                $userMessage = 'Unable to generate quiz. Please try again.';
                if (str_contains($lastError ?? '', 'quota') || str_contains($lastError ?? '', 'rate')) {
                    $userMessage = 'Service is busy. Please try again in a moment.';
                }

                return response()->json([
                    'success' => false,
                    'message' => $userMessage,
                    'error_details' => config('app.debug') ? $lastError : null,
                ], 500);
            }

            // Record usage after successful quiz generation
            if ($user) {
                $this->usageLimitService->recordUsage($user, 'topic_quiz');
            }

            $reasoningResult = [
                'title' => "Reasoning Quiz: {$category}",
                'description' => "Reasoning & Aptitude - {$difficulty} level",
                'questions' => $quizData['questions'],
                'total_questions' => count($quizData['questions']),
                'difficulty' => $difficulty,
                'duration' => $duration,
                'language' => $language,
                'category' => $category,
                'type' => 'reasoning',
            ];

            return response()->json([
                'success' => true,
                'quiz' => $reasoningResult,
            ]);

        } catch (\Exception $e) {
            \Log::error('Reasoning quiz generation failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to generate quiz. Please try again.',
                'error_type' => config('app.debug') ? get_class($e) : null,
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

            $cachedQuiz = ResourceAuthorizer::ownedQuizCache($user, (int) $quizCacheId);

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

            $cachedQuiz = ResourceAuthorizer::ownedQuizCache($user, (int) $quizCacheId);

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
     * Extract correct answer from various AI response field names.
     */
    private function extractCorrectAnswerRaw(array $question): mixed
    {
        foreach (['correct_answer', 'correctAnswer', 'correct_option', 'answer', 'correct'] as $key) {
            if (array_key_exists($key, $question) && $question[$key] !== '' && $question[$key] !== null) {
                return $question[$key];
            }
        }

        if (array_key_exists('correct_index', $question) && $question['correct_index'] !== '' && $question['correct_index'] !== null) {
            return $question['correct_index'];
        }

        return '';
    }

    /**
     * Resolve 0-based correct option index for MCQ questions.
     */
    private function resolveMcqCorrectIndex(mixed $answer, array $options): int
    {
        $keys = ['A', 'B', 'C', 'D'];
        $normalizedOpts = [];

        foreach ($options as $key => $value) {
            $normalizedOpts[strtoupper((string) $key)] = trim((string) $value);
        }

        if (is_int($answer) || (is_string($answer) && ctype_digit(trim($answer)))) {
            $n = (int) $answer;
            if ($n >= 1 && $n <= 4) {
                return $n - 1;
            }
            if ($n >= 0 && $n <= 3) {
                return $n;
            }
        }

        $answerStr = strtoupper(trim((string) $answer));

        if (preg_match('/^([A-D])$/', $answerStr, $match)) {
            return ord($match[1]) - ord('A');
        }

        if (preg_match('/(?:^|[^A-Z])([A-D])(?:[^A-Z]|$)/', $answerStr, $match)) {
            return ord($match[1]) - ord('A');
        }

        foreach ($keys as $index => $key) {
            if (isset($normalizedOpts[$key]) && strcasecmp($normalizedOpts[$key], (string) $answer) === 0) {
                return $index;
            }
        }

        $listIndex = 0;
        foreach ($options as $value) {
            if (strcasecmp(trim((string) $value), (string) $answer) === 0) {
                return $listIndex;
            }
            $listIndex++;
        }

        \Log::warning('Could not resolve MCQ correct answer, defaulting to A', [
            'answer' => $answer,
            'options' => $options,
        ]);

        return 0;
    }

    /**
     * Calculate optimal question count based on duration and difficulty
     *
     * FORMULA: questions = duration ÷ time_per_question
     *
     * Time per question by difficulty:
     * - Easy: 1 min per question (fast, simple questions)
     * - Medium: 1.5 min per question (normal pace)
     * - Hard: 2 min per question (complex, need thinking)
     *
     * Examples:
     * - 5 min easy = 5 questions
     * - 5 min hard = 2-3 questions
     * - 10 min easy = 10 questions
     * - 10 min medium = 6-7 questions
     * - 15 min hard = 7-8 questions
     * - 30 min medium = 20 questions
     *
     * @param int $durationMinutes Duration in minutes (3-180)
     * @param string $difficulty Difficulty level (easy, medium, hard)
     * @return int Calculated question count
     */
    private function calculateQuestionCount(int $durationMinutes, string $difficulty): int
    {
        // Time per question based on difficulty (in minutes)
        // Easy = fast questions, Hard = slow/complex questions
        $timePerQuestion = match ($difficulty) {
            'easy' => 1.0,      // Easy: 1 min each (simple recall)
            'medium' => 1.5,    // Medium: 1.5 min each (some thinking)
            'hard' => 2.0,      // Hard: 2 min each (complex analysis)
            default => 1.5,     // Default to medium
        };

        // Calculate question count directly from duration
        $questionCount = (int) floor($durationMinutes / $timePerQuestion);

        // Apply reasonable bounds only (no forced minimums per duration)
        // Min 3 questions (too few = not useful)
        // Max 50 questions (too many = AI generation issues)
        return max(3, min($questionCount, 50));
    }

    /**
     * Get recommended duration based on question count and difficulty
     *
     * @param int $questionCount Number of questions
     * @param string $difficulty Difficulty level
     * @return int Recommended duration in minutes
     */
    private function calculateDuration(int $questionCount, string $difficulty): int
    {
        $timePerQuestion = match ($difficulty) {
            'easy' => 1.0,
            'medium' => 1.5,
            'hard' => 2.0,
            default => 1.5,
        };

        $duration = (int) ceil($questionCount * $timePerQuestion);

        // Apply reasonable bounds
        return max(3, min($duration, 180));
    }
}
