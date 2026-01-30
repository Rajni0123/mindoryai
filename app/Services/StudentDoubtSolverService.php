<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class StudentDoubtSolverService
{
    protected $apiKey;
    protected $apiUrl;
    protected $systemPrompt;

    public function __construct()
    {
        $this->apiKey = env('OPENAI_API_KEY');
        $this->apiUrl = env('OPENAI_API_URL', 'https://api.openai.com/v1/chat/completions');
        $this->systemPrompt = $this->getStudentTutorSystemPrompt();
    }

    /**
     * Professional Student-Focused System Prompt
     */
    protected function getStudentTutorSystemPrompt(): string
    {
        return <<<PROMPT
You are an expert AI tutor and doubt solver for students with the following capabilities:

🎓 YOUR ROLE:
- Patient and encouraging teacher who explains concepts clearly
- Solve doubts step-by-step with detailed explanations
- Analyze images (handwritten notes, diagrams, equations, PDFs)
- Provide solutions in a student-friendly format

📚 RESPONSE GUIDELINES:

1. **Understanding the Question:**
   - First, acknowledge what the student is asking
   - Identify the subject/topic clearly

2. **Step-by-Step Solutions:**
   - Break complex problems into simple steps
   - Use numbered steps for mathematical/logical problems
   - Explain WHY each step is necessary
   - Show calculations clearly

3. **Format for Different Subjects:**

   **For Math/Physics/Chemistry:**
   - Clear step-by-step solution
   - Show all formulas used
   - Explain the logic behind each step
   - Provide final answer in a box or highlight
   - Add tips to avoid common mistakes

   **For Theory Subjects:**
   - Start with a brief answer
   - Provide detailed explanation with points
   - Use examples and analogies
   - Add memory tricks or mnemonics when helpful

   **For Image/PDF Analysis:**
   - First describe what you see in the image
   - Identify the question or problem
   - Solve it step-by-step
   - If handwriting is unclear, mention it politely

4. **FORMATTING RULES:**
   - Use **bold** for key concepts and formulas
   - Use bullet points (•) for listing concepts
   - Use numbered lists (1,2,3) for sequential steps
   - Use > for important notes or tips
   - Use ``` for code or formatted equations
   - Add emojis sparingly for engagement (✓, ❌, 💡, ⚠️)

5. **TONE:**
   - Encouraging and supportive
   - Never make students feel dumb
   - Celebrate understanding
   - Patient with repeated questions
   - Use simple language (explain jargon)

6. **ADDITIONAL HELP:**
   - Suggest related concepts to study
   - Provide practice tips
   - Recommend what to focus on
   - Offer memory techniques

7. **LANGUAGE:**
   - If student asks in Hindi/Hinglish, respond in the same
   - Mix Hindi-English naturally for Indian students
   - Use "aap" for respect, "tum" for friendly tone

Remember: Your goal is to make students UNDERSTAND, not just get answers.
PROMPT;
    }

    /**
     * Solve text-based doubt
     *
     * @param string $question Student's question
     * @param string|null $subject Subject name (optional)
     * @param array $conversationHistory Previous conversation (optional)
     * @return string AI response
     */
    public function solveTextDoubt(string $question, ?string $subject = null, array $conversationHistory = []): string
    {
        $messages = [
            ['role' => 'system', 'content' => $this->systemPrompt]
        ];

        // Add conversation history
        if (!empty($conversationHistory)) {
            $messages = array_merge($messages, $conversationHistory);
        }

        // Add subject context if provided
        $userMessage = $question;
        if ($subject) {
            $userMessage = "[Subject: {$subject}]\n\n{$question}";
        }

        $messages[] = ['role' => 'user', 'content' => $userMessage];

        try {
            $http = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(60);

            if (app()->environment('local')) {
                $http = $http->withoutVerifying();
            }

            $response = $http->post($this->apiUrl, [
                'model' => 'gpt-4o',  // Using gpt-4o for better explanations
                'messages' => $messages,
                'temperature' => 0.7,
                'max_tokens' => 2500,
                'top_p' => 0.9
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['choices'][0]['message']['content'] ?? 'No response generated.';
            }

            Log::error('OpenAI API Error', ['response' => $response->body()]);
            return '❌ Error: Unable to get response from AI.';

        } catch (Exception $e) {
            Log::error('Student Doubt Solver Error', ['error' => $e->getMessage()]);
            return "❌ Error: {$e->getMessage()}";
        }
    }

    /**
     * Solve image-based doubt (handwritten notes, diagrams, equations, etc.)
     *
     * @param string $imagePath Path to image file or base64 encoded image
     * @param string|null $question Additional context/question (optional)
     * @param string|null $subject Subject name (optional)
     * @return string AI response
     */
    public function solveImageDoubt(string $imagePath, ?string $question = null, ?string $subject = null): string
    {
        // Check if it's already base64 or a file path
        if (file_exists($imagePath)) {
            $imageData = base64_encode(file_get_contents($imagePath));
            $mimeType = mime_content_type($imagePath);
        } else {
            // Assume it's already base64
            $imageData = $imagePath;
            $mimeType = 'image/jpeg'; // Default
        }

        $messages = [
            ['role' => 'system', 'content' => $this->systemPrompt]
        ];

        // Build user message content
        $textContent = "Please analyze this image and solve the problem/doubt shown in it.";

        if ($subject) {
            $textContent = "[Subject: {$subject}]\n\n" . $textContent;
        }

        if ($question) {
            $textContent .= "\n\nAdditional context: {$question}";
        }

        $messages[] = [
            'role' => 'user',
            'content' => [
                [
                    'type' => 'text',
                    'text' => $textContent
                ],
                [
                    'type' => 'image_url',
                    'image_url' => [
                        'url' => "data:{$mimeType};base64,{$imageData}"
                    ]
                ]
            ]
        ];

        try {
            $http = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(90);

            if (app()->environment('local')) {
                $http = $http->withoutVerifying();
            }

            $response = $http->post($this->apiUrl, [
                'model' => 'gpt-4o',  // gpt-4o has vision capabilities
                'messages' => $messages,
                'max_tokens' => 2500,
                'temperature' => 0.7
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['choices'][0]['message']['content'] ?? 'No response generated.';
            }

            Log::error('OpenAI Vision API Error', ['response' => $response->body()]);
            return '❌ Error processing image: Unable to analyze image.';

        } catch (Exception $e) {
            Log::error('Image Doubt Solver Error', ['error' => $e->getMessage()]);
            return "❌ Error processing image: {$e->getMessage()}";
        }
    }

    /**
     * Solve PDF content doubt
     *
     * @param string $pdfText Extracted text from PDF
     * @param string|null $question Specific question about the PDF
     * @param string|null $subject Subject name
     * @return string AI response
     */
    public function solvePdfDoubt(string $pdfText, ?string $question = null, ?string $subject = null): string
    {
        $messages = [
            ['role' => 'system', 'content' => $this->systemPrompt]
        ];

        // Limit text to avoid token limits (approximately 4000 characters)
        $limitedText = mb_substr($pdfText, 0, 4000);

        $userMessage = "I have the following content from a PDF:\n\n---\n{$limitedText}\n---\n\n";

        if ($subject) {
            $userMessage = "[Subject: {$subject}]\n\n" . $userMessage;
        }

        if ($question) {
            $userMessage .= "My question: {$question}";
        } else {
            $userMessage .= "Please explain the key concepts from this content.";
        }

        $messages[] = ['role' => 'user', 'content' => $userMessage];

        try {
            $http = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(60);

            if (app()->environment('local')) {
                $http = $http->withoutVerifying();
            }

            $response = $http->post($this->apiUrl, [
                'model' => 'gpt-4o',
                'messages' => $messages,
                'temperature' => 0.7,
                'max_tokens' => 2500
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['choices'][0]['message']['content'] ?? 'No response generated.';
            }

            Log::error('OpenAI PDF API Error', ['response' => $response->body()]);
            return '❌ Error: Unable to process PDF content.';

        } catch (Exception $e) {
            Log::error('PDF Doubt Solver Error', ['error' => $e->getMessage()]);
            return "❌ Error: {$e->getMessage()}";
        }
    }

    /**
     * Generate quiz questions based on topic
     *
     * @param string $topic Topic name
     * @param string|null $subject Subject name
     * @param int $numberOfQuestions Number of questions to generate (default: 5)
     * @param string $difficulty Difficulty level: easy, medium, hard (default: medium)
     * @return string Generated quiz in JSON format
     */
    public function generateQuiz(string $topic, ?string $subject = null, int $numberOfQuestions = 5, string $difficulty = 'medium'): string
    {
        // Sanitize input to prevent encoding issues
        $topic = $this->sanitizeString($topic);
        $subject = $subject ? $this->sanitizeString($subject) : null;

        // Extract year filter, exam type, and language if present, then clean the topic
        $yearFilter = "";
        $examType = "";
        $language = "english"; // default
        $cleanTopic = $topic;

        // Extract exam type like [JEE] or [UPSC]
        if (preg_match('/^\[([^\]]+)\]\s*/', $cleanTopic, $examMatch)) {
            $examType = $examMatch[1];
            $cleanTopic = preg_replace('/^\[([^\]]+)\]\s*/', '', $cleanTopic);
        }

        // Extract year from STRICT YEAR FILTER
        if (preg_match('/\[STRICT YEAR FILTER:.*?(\d{4}(?:-\d{4})?)/s', $cleanTopic, $matches)) {
            $yearFilter = $matches[1];
            $cleanTopic = preg_replace('/\s*\[STRICT YEAR FILTER:[^\]]*\]/s', '', $cleanTopic);
        }

        // Extract language tag if present
        if (preg_match('/\[Language:\s*(\w+)\]/i', $cleanTopic, $langMatch)) {
            $language = strtolower($langMatch[1]);
            $cleanTopic = preg_replace('/\s*\[Language:[^\]]*\]/s', '', $cleanTopic);
        }

        // Final cleanup - trim whitespace
        $cleanTopic = trim($cleanTopic);

        // Build a clean, simple prompt
        $examInfo = $examType ? "Exam: {$examType}, " : "";
        $subjectInfo = $subject ? "Subject: {$subject}, " : "";
        $yearInfo = $yearFilter ? "Year: {$yearFilter} only, " : "";

        // Language instruction - important for Hindi
        $languageInstruction = "";
        $languageRule = "";
        if ($language === 'hindi') {
            $languageInstruction = "\nLanguage: HINDI (हिंदी)";
            $languageRule = "\n3. MUST write in HINDI using Devanagari script (हिंदी में लिखें)";
        }

        $userMessage = <<<PROMPT
Generate exactly {$numberOfQuestions} MCQ questions.

{$examInfo}{$subjectInfo}{$yearInfo}Topic: {$cleanTopic}
Difficulty: {$difficulty}{$languageInstruction}

Rules:
1. Each question must have exactly 4 options (A, B, C, D)
2. Return valid JSON only, no extra text{$languageRule}

JSON format:
{"questions":[{"question":"प्रश्न?","options":{"A":"विकल्प","B":"विकल्प","C":"विकल्प","D":"विकल्प"},"correct_answer":"A","explanation":"व्याख्या"}]}
PROMPT;

        // If English, use English example
        if ($language !== 'hindi') {
            $userMessage = <<<PROMPT
Generate exactly {$numberOfQuestions} MCQ questions.

{$examInfo}{$subjectInfo}{$yearInfo}Topic: {$cleanTopic}
Difficulty: {$difficulty}

Rules:
1. Each question must have exactly 4 options (A, B, C, D)
2. Return valid JSON only, no extra text

JSON format:
{"questions":[{"question":"Q?","options":{"A":"","B":"","C":"","D":""},"correct_answer":"A","explanation":"Why"}]}
PROMPT;
        }

        try {
            // Get the first active Google Gemini model from database
            $activeGeminiModel = \App\Models\AiModel::where('provider', 'google')
                ->where('is_active', true)
                ->orderBy('order')
                ->first();

            $modelIdentifier = $activeGeminiModel
                ? $activeGeminiModel->model_identifier
                : 'gemini-2.0-flash';

            Log::info('Using Gemini model for quiz generation', [
                'model' => $modelIdentifier,
                'model_name' => $activeGeminiModel?->name,
            ]);

            $geminiService = new GeminiService(
                feature: 'quiz',
                modelName: $modelIdentifier,
                userId: auth()->id()
            );

            // Calculate token limit based on question count (roughly 200 tokens per question)
            $tokenLimit = max(2048, $numberOfQuestions * 250);

            Log::info('Generating quiz with prompt', [
                'exam' => $examType ?: 'none',
                'topic' => $cleanTopic,
                'subject' => $subject,
                'year' => $yearFilter ?: 'any',
                'language' => $language,
                'questions' => $numberOfQuestions,
                'difficulty' => $difficulty,
                'token_limit' => $tokenLimit,
                'prompt_length' => strlen($userMessage),
            ]);

            $response = $geminiService->generateContent(
                userPrompt: $userMessage,
                options: [
                    'temperature' => 0.7,
                    'maxOutputTokens' => $tokenLimit,
                    'jsonMode' => true,
                ]
            );

            $content = $response['content'] ?? '';

            // Sanitize the response to fix encoding issues
            $content = $this->sanitizeString($content);

            Log::info('Quiz generation response', [
                'content_length' => strlen($content),
                'content_preview' => substr($content, 0, 200),
            ]);

            if (empty($content)) {
                Log::warning('Empty quiz response from AI');
                return '{"questions":[]}';
            }

            return $content;

        } catch (Exception $e) {
            Log::error('Quiz Generation Error', [
                'error' => $e->getMessage(),
                'topic' => $topic,
                'subject' => $subject,
            ]);
            throw $e; // Re-throw to let controller handle it
        }
    }

    /**
     * Sanitize string to fix UTF-8 encoding issues
     * This is crucial for handling responses from AI APIs
     */
    private function sanitizeString(string $string): string
    {
        // Remove BOM if present
        $string = preg_replace('/^\xEF\xBB\xBF/', '', $string);

        // Remove null bytes
        $string = str_replace("\0", '', $string);

        // Use iconv to strip invalid UTF-8 sequences
        $cleaned = @iconv('UTF-8', 'UTF-8//IGNORE', $string);
        if ($cleaned !== false) {
            $string = $cleaned;
        }

        // Remove control characters except newline, tab, carriage return
        $string = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $string);

        return $string;
    }

    /**
     * Generate quiz from uploaded image
     * STRICTLY uses image content only - no outside knowledge
     *
     * @param \Illuminate\Http\UploadedFile $image Uploaded image file
     * @param int $numberOfQuestions Number of questions (default: 5)
     * @param string $difficulty Difficulty level (default: medium)
     * @return string Generated quiz
     */
    public function generateQuizFromImage($image, int $numberOfQuestions = 5, string $difficulty = 'medium'): string
    {
        // Read and encode image
        $imageBase64 = base64_encode(file_get_contents($image->getRealPath()));
        $imageMimeType = $image->getMimeType();

        // Create strict image-only prompt
        $userMessage = <<<PROMPT
You are a quiz generator. Look at the uploaded image and create questions from it ONLY.

MOST IMPORTANT RULE - READ CAREFULLY:
⛔ DO NOT use any outside knowledge, facts, or information
⛔ DO NOT make up questions from your training data
✅ ONLY create questions based on what is VISIBLE in the image
✅ If the image has questions, create SIMILAR questions with different values
✅ If the image has notes, ask about concepts FROM those notes
✅ If the image has problems, create similar problems

IF the image contains questions (like a test paper):
- Create NEW questions following the SAME pattern
- Change numbers, names, or values BUT keep the same concept
- Do NOT copy exact questions - make variations

IF the image contains notes or study material:
- Ask questions that can be answered by reading the notes
- Every answer must be findable in the image

Generate EXACTLY {$numberOfQuestions} questions in this format:

###QUESTION1###
[Question based ONLY on image content]
###OPTIONS###
A) [Option from image or variation]
B) [Option from image or variation]
C) [Option from image or variation]
D) [Option from image or variation]
###ANSWER###
[A/B/C/D]
###EXPLANATION###
[Based on image content]

Repeat for all {$numberOfQuestions} questions.
PROMPT;

        try {
            // Get the first active Google Gemini model with vision support from database
            $activeGeminiModel = \App\Models\AiModel::where('provider', 'google')
                ->where('is_active', true)
                ->where('supports_vision', true)
                ->orderBy('order')
                ->first();

            $modelIdentifier = $activeGeminiModel
                ? $activeGeminiModel->model_identifier
                : 'gemini-2.0-flash';

            Log::info('Using Gemini model for image quiz generation', [
                'model' => $modelIdentifier,
                'model_name' => $activeGeminiModel?->name,
            ]);

            $geminiService = new GeminiService(
                feature: 'quiz',
                modelName: $modelIdentifier,
                userId: auth()->id()
            );

            $response = $geminiService->generateContentWithVision(
                userPrompt: $userMessage,
                imageData: $imageBase64,
                mimeType: $imageMimeType,
                options: [
                    'temperature' => 0.7,
                    'maxOutputTokens' => 4096,
                ]
            );

            return $response['content'] ?? 'No quiz generated from image.';

        } catch (Exception $e) {
            Log::error('Image Quiz Generation Error', ['error' => $e->getMessage()]);
            return "❌ Error: {$e->getMessage()}";
        }
    }

    /**
     * Generate practice problems for a topic
     *
     * @param string $topic Topic name
     * @param string|null $subject Subject name
     * @param int $numberOfProblems Number of problems (default: 5)
     * @param string $difficulty Difficulty level
     * @return string Generated practice problems
     */
    public function generatePracticeProblems(string $topic, ?string $subject = null, int $numberOfProblems = 5, string $difficulty = 'medium'): string
    {
        $messages = [
            ['role' => 'system', 'content' => $this->systemPrompt]
        ];

        $userMessage = <<<PROMPT
Generate {$numberOfProblems} practice problems on the topic: "{$topic}"

**Requirements:**
- Difficulty: {$difficulty}
- Include a mix of problem types
- Provide hints for each problem (not full solutions)
- Make problems progressively challenging
- Include real-world applications where possible

**Format:**
For each problem:
1. **Problem {number}:** [Problem statement]

   **Hint:** [Helpful hint without giving away the answer]

   **Difficulty:** ⭐⭐⭐ (use stars)

Make the problems engaging and student-friendly!
PROMPT;

        if ($subject) {
            $userMessage = "[Subject: {$subject}]\n\n" . $userMessage;
        }

        $messages[] = ['role' => 'user', 'content' => $userMessage];

        try {
            $http = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(60);

            if (app()->environment('local')) {
                $http = $http->withoutVerifying();
            }

            $response = $http->post($this->apiUrl, [
                'model' => 'gpt-4o',
                'messages' => $messages,
                'temperature' => 0.8,
                'max_tokens' => 2500
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['choices'][0]['message']['content'] ?? 'No problems generated.';
            }

            Log::error('Practice Problems Error', ['response' => $response->body()]);
            return '❌ Error: Unable to generate practice problems.';

        } catch (Exception $e) {
            Log::error('Practice Problems Error', ['error' => $e->getMessage()]);
            return "❌ Error: {$e->getMessage()}";
        }
    }

    /**
     * Explain a concept with examples
     *
     * @param string $concept Concept to explain
     * @param string|null $subject Subject name
     * @param string $level Student level: beginner, intermediate, advanced
     * @return string Detailed explanation with examples
     */
    public function explainConcept(string $concept, ?string $subject = null, string $level = 'intermediate'): string
    {
        $messages = [
            ['role' => 'system', 'content' => $this->systemPrompt]
        ];

        $userMessage = <<<PROMPT
Explain the concept: "{$concept}" for {$level} level students.

**Include:**
1. **Simple Definition:** What is it in simple terms?
2. **Detailed Explanation:** How does it work?
3. **Real-World Examples:** At least 2-3 relatable examples
4. **Visual Description:** Describe how to visualize it
5. **Common Misconceptions:** What students often get wrong
6. **Memory Tricks:** Mnemonics or tricks to remember
7. **Related Concepts:** What else should they learn?
8. **Practice Tips:** How to master this concept

Make it engaging, use analogies, and adapt the language to the student level!
PROMPT;

        if ($subject) {
            $userMessage = "[Subject: {$subject}]\n\n" . $userMessage;
        }

        $messages[] = ['role' => 'user', 'content' => $userMessage];

        try {
            $http = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(60);

            if (app()->environment('local')) {
                $http = $http->withoutVerifying();
            }

            $response = $http->post($this->apiUrl, [
                'model' => 'gpt-4o',
                'messages' => $messages,
                'temperature' => 0.7,
                'max_tokens' => 2500
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['choices'][0]['message']['content'] ?? 'No explanation generated.';
            }

            Log::error('Concept Explanation Error', ['response' => $response->body()]);
            return '❌ Error: Unable to explain concept.';

        } catch (Exception $e) {
            Log::error('Concept Explanation Error', ['error' => $e->getMessage()]);
            return "❌ Error: {$e->getMessage()}";
        }
    }

    /**
     * Get step-by-step hints for solving a problem
     *
     * @param string $problem The problem to solve
     * @param string|null $subject Subject name
     * @param int $currentStep Current step (0 = just hints, 1-5 = progressive hints)
     * @return string Hint for the current step
     */
    public function getHint(string $problem, ?string $subject = null, int $currentStep = 0): string
    {
        $messages = [
            ['role' => 'system', 'content' => $this->systemPrompt]
        ];

        $hintLevel = match($currentStep) {
            0 => "Provide only a gentle nudge in the right direction. Don't give away the solution.",
            1 => "Give the first step or approach to start with.",
            2 => "Provide the next logical step or formula to use.",
            3 => "Give more detailed guidance but still let student think.",
            4 => "Provide most of the solution with explanation.",
            default => "Provide the complete step-by-step solution."
        };

        $userMessage = <<<PROMPT
Problem: {$problem}

Hint Level: {$currentStep}/5

{$hintLevel}

**Remember:**
- Guide the student to think
- Don't just give the answer
- Encourage problem-solving skills
- Use encouraging language
PROMPT;

        if ($subject) {
            $userMessage = "[Subject: {$subject}]\n\n" . $userMessage;
        }

        $messages[] = ['role' => 'user', 'content' => $userMessage];

        try {
            $http = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(60);

            if (app()->environment('local')) {
                $http = $http->withoutVerifying();
            }

            $response = $http->post($this->apiUrl, [
                'model' => 'gpt-4o',
                'messages' => $messages,
                'temperature' => 0.7,
                'max_tokens' => 1000
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['choices'][0]['message']['content'] ?? 'No hint available.';
            }

            Log::error('Hint Generation Error', ['response' => $response->body()]);
            return '❌ Error: Unable to generate hint.';

        } catch (Exception $e) {
            Log::error('Hint Generation Error', ['error' => $e->getMessage()]);
            return "❌ Error: {$e->getMessage()}";
        }
    }

    /**
     * Summarize a topic for quick revision
     *
     * @param string $topic Topic to summarize
     * @param string|null $subject Subject name
     * @return string Summary with key points
     */
    public function summarizeTopic(string $topic, ?string $subject = null): string
    {
        $messages = [
            ['role' => 'system', 'content' => $this->systemPrompt]
        ];

        $userMessage = <<<PROMPT
Create a comprehensive yet concise summary of: "{$topic}"

**Include:**
1. **📌 Key Points:** Main concepts (bullet points)
2. **📐 Important Formulas/Rules:** If applicable
3. **💡 Must Remember:** Critical facts
4. **⚠️ Common Mistakes:** What to avoid
5. **🎯 Exam Tips:** What examiners look for
6. **🔗 Quick Recap:** One-liner summary

Make it perfect for last-minute revision before exams!
PROMPT;

        if ($subject) {
            $userMessage = "[Subject: {$subject}]\n\n" . $userMessage;
        }

        $messages[] = ['role' => 'user', 'content' => $userMessage];

        try {
            $http = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(60);

            if (app()->environment('local')) {
                $http = $http->withoutVerifying();
            }

            $response = $http->post($this->apiUrl, [
                'model' => 'gpt-4o',
                'messages' => $messages,
                'temperature' => 0.7,
                'max_tokens' => 2000
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['choices'][0]['message']['content'] ?? 'No summary generated.';
            }

            Log::error('Topic Summary Error', ['response' => $response->body()]);
            return '❌ Error: Unable to generate summary.';

        } catch (Exception $e) {
            Log::error('Topic Summary Error', ['error' => $e->getMessage()]);
            return "❌ Error: {$e->getMessage()}";
        }
    }
}
