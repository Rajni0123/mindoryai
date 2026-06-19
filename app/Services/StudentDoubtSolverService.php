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
     * STRICT NO-MARKDOWN format
     */
    protected function getStudentTutorSystemPrompt(): string
    {
        return <<<PROMPT
You are an expert AI tutor for BlinkStudy students.

FORMATTING RULES:
✓ Use **bold** for important terms and headings
✓ Use • or - for bullet points
✓ Use 1. 2. 3. for numbered steps
✓ Use → for arrows

VISUAL ICONS - USE THESE TO MAKE EXPLANATIONS ENGAGING:
You can use inline icons to illustrate concepts. Use syntax: <icon name="iconname"/>

Available icons by subject:
• Physics: <icon name="sun"/> <icon name="atom"/> <icon name="magnet"/> <icon name="wave"/> <icon name="battery"/> <icon name="energy"/>
• Chemistry: <icon name="flask"/> <icon name="molecule"/> <icon name="water"/> <icon name="fire"/> <icon name="crystal"/>
• Biology: <icon name="dna"/> <icon name="cell"/> <icon name="heart"/> <icon name="plant"/> <icon name="brain"/> <icon name="leaf"/>
• Maths: <icon name="graph"/> <icon name="triangle"/> <icon name="circle"/> <icon name="calculator"/> <icon name="infinity"/> <icon name="formula"/>
• History: <icon name="crown"/> <icon name="sword"/> <icon name="scroll"/>
• Geography: <icon name="mountain"/> <icon name="river"/> <icon name="volcano"/> <icon name="compass"/> <icon name="cloud"/>
• General: <icon name="bulb"/> <icon name="star"/> <icon name="warning"/> <icon name="note"/> <icon name="tick"/> <icon name="cross"/> <icon name="arrow"/>

EXAMPLE RESPONSE WITH ICONS:

**PHOTOSYNTHESIS** <icon name="leaf"/>

**Definition:** Process by which plants <icon name="plant"/> make food using sunlight <icon name="sun"/>.

**Equation:** 6CO₂ + 6H₂O + Light → C₆H₁₂O₆ + 6O₂

**Key Points:** <icon name="bulb"/>
• Light reactions occur in thylakoid
• Dark reactions occur in stroma
• Chlorophyll absorbs light <icon name="sun"/>

**Steps:**
1. Light is absorbed <icon name="sun"/>
2. Water <icon name="water"/> is split
3. ATP is formed <icon name="energy"/>
4. CO₂ is fixed

YOUR ROLE:
• Expert tutor for Indian students (JEE, NEET, CBSE)
• Solve problems step-by-step
• Be encouraging and patient
• Respond in student's language (Hindi/English mix)
• Use icons sparingly (2-5 per response) to highlight key concepts
• Make learning visual and engaging!
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
            ])->connectTimeout(8)->timeout(35);  // OPTIMIZED: Faster connection and response

            if (app()->environment('local')) {
                $http = $http->withoutVerifying();
            }

            $response = $http->post($this->apiUrl, [
                'model' => 'gpt-4o-mini',  // OPTIMIZED: Faster model
                'messages' => $messages,
                'temperature' => 0.7,
                'max_tokens' => 2000,  // Reduced for faster response
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
            ])->connectTimeout(8)->timeout(45);  // OPTIMIZED: Reduced from 90s

            if (app()->environment('local')) {
                $http = $http->withoutVerifying();
            }

            $response = $http->post($this->apiUrl, [
                'model' => 'gpt-4o-mini',  // OPTIMIZED: Faster model with vision
                'messages' => $messages,
                'max_tokens' => 2000,  // Reduced for faster response
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
            ])->connectTimeout(8)->timeout(35);

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
     * ROBUST VERSION - Multiple fallbacks to ensure quiz generation works
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

        // Limit questions to prevent timeout/truncation
        $numberOfQuestions = min($numberOfQuestions, 15);

        // Extract metadata from topic
        $cleanTopic = $topic;
        $language = "english";
        $examType = "";

        // Extract exam type like [JEE] or [UPSC]
        if (preg_match('/^\[([^\]]+)\]\s*/', $cleanTopic, $examMatch)) {
            $examType = $examMatch[1];
            $cleanTopic = preg_replace('/^\[([^\]]+)\]\s*/', '', $cleanTopic);
        }

        // Extract language tag
        if (preg_match('/\[Language:\s*(\w+)\]/i', $cleanTopic, $langMatch)) {
            $language = strtolower($langMatch[1]);
            $cleanTopic = preg_replace('/\s*\[Language:[^\]]*\]/s', '', $cleanTopic);
        }

        // Remove other metadata tags
        $cleanTopic = preg_replace('/\s*\[[^\]]*\]/s', '', $cleanTopic);
        $cleanTopic = trim($cleanTopic);

        // Build simple, clear prompt that reliably produces JSON
        $topicLine = $examType ? "{$examType}: {$cleanTopic}" : $cleanTopic;
        $subjectLine = $subject ? "Subject: {$subject}" : "";

        // Use a very simple, structured prompt
        $userMessage = "Generate {$numberOfQuestions} MCQ quiz questions about: {$topicLine}
{$subjectLine}
Difficulty: {$difficulty}

Return ONLY this JSON format:
{\"questions\":[{\"question\":\"What is X?\",\"options\":{\"A\":\"Option 1\",\"B\":\"Option 2\",\"C\":\"Option 3\",\"D\":\"Option 4\"},\"correct_answer\":\"A\",\"explanation\":\"Brief explanation\"}]}

Important: Return valid JSON only. No markdown, no extra text.";

        if ($language === 'hindi') {
            $userMessage .= "\n\nWrite questions in Hindi (हिंदी में लिखें).";
        } elseif ($language === 'hinglish') {
            $userMessage .= "\n\nWrite questions in Hinglish (natural mix of Hindi and English, as Indian students speak).";
        }

        // OpenAI first (fast + accurate JSON), Gemini as fallback
        $providers = ['openai', 'gemini'];
        $lastError = null;

        foreach ($providers as $provider) {
            try {
                Log::info("Trying quiz generation with {$provider}", [
                    'topic' => $cleanTopic,
                    'questions' => $numberOfQuestions,
                ]);

                if ($provider === 'openai') {
                    $content = $this->generateQuizWithOpenAI($userMessage, $numberOfQuestions);
                } else {
                    $content = $this->generateQuizWithGemini($userMessage, $numberOfQuestions);
                }

                // Validate the response has questions
                if (!empty($content) && $this->validateQuizJson($content)) {
                    Log::info("Quiz generation successful with {$provider}", [
                        'content_length' => strlen($content),
                    ]);
                    return $content;
                }

                Log::warning("{$provider} returned invalid quiz format, trying next provider");
                $lastError = "Invalid JSON format from {$provider}";

            } catch (Exception $e) {
                Log::warning("{$provider} quiz generation failed: " . $e->getMessage());
                $lastError = $e->getMessage();
            }
        }

        // If all providers failed, throw the last error
        throw new Exception($lastError ?? 'All quiz generation providers failed');
    }

    /**
     * Generate quiz with Gemini
     */
    private function generateQuizWithGemini(string $prompt, int $numberOfQuestions): string
    {
        $activeGeminiModel = \App\Models\AiModel::where('provider', 'google')
            ->where('is_active', true)
            ->orderBy('order')
            ->first();

        $modelIdentifier = $activeGeminiModel?->model_identifier ?? 'gemini-2.0-flash';

        $geminiService = new GeminiService(
            feature: 'quiz',
            modelName: $modelIdentifier,
            userId: auth()->id()
        );

        // High token limit to prevent truncation
        $tokenLimit = max(4096, $numberOfQuestions * 800);

        $response = $geminiService->generateContent(
            userPrompt: $prompt,
            options: [
                'temperature' => 0.7,
                'maxOutputTokens' => $tokenLimit,
                'jsonMode' => true,
                'timeout' => 35,
                'connect_timeout' => 8,
            ]
        );

        $content = $response['content'] ?? '';
        return $this->sanitizeString($content);
    }

    /**
     * Validate quiz JSON has required structure
     */
    private function validateQuizJson(string $content): bool
    {
        if (empty($content)) return false;

        // Try to decode JSON
        $decoded = @json_decode($content, true);
        if ($decoded === null) {
            // Try to extract JSON from response
            if (preg_match('/\{[\s\S]*"questions"[\s\S]*\}/s', $content, $matches)) {
                $decoded = @json_decode($matches[0], true);
            }
        }

        // Check if questions array exists and has items
        if (!$decoded || !isset($decoded['questions']) || !is_array($decoded['questions'])) {
            return false;
        }

        // Check if at least one question has required fields
        foreach ($decoded['questions'] as $q) {
            if (isset($q['question']) && isset($q['options']) && isset($q['correct_answer'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate quiz using OpenAI as fallback
     * Uses GPT-4o-mini with guaranteed JSON mode
     */
    private function generateQuizWithOpenAI(string $prompt, int $numberOfQuestions): string
    {
        Log::info('Using OpenAI for quiz generation', [
            'model' => 'gpt-4o-mini',
            'questions' => $numberOfQuestions,
        ]);

        // Try to get API key from multiple sources
        $apiKey = \App\Models\FrontendConfig::getValue('ai.openai_api_key', '');
        if (empty($apiKey)) {
            $apiKey = \App\Models\Setting::get('openai_api_key', '');
        }
        if (empty($apiKey)) {
            $apiKey = config('ai.openai.api_key', env('OPENAI_API_KEY', ''));
        }

        if (empty($apiKey)) {
            throw new Exception('OpenAI API key not configured');
        }

        // High token limit to prevent truncation
        $tokenLimit = max(4096, $numberOfQuestions * 800);

        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])
        ->withOptions(['verify' => config('app.env') !== 'local'])
        ->connectTimeout(8)
        ->timeout(40)
        ->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-4o-mini',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a quiz generator. You MUST return ONLY valid JSON with this exact format: {"questions":[{"question":"...","options":{"A":"...","B":"...","C":"...","D":"..."},"correct_answer":"A","explanation":"..."}]}. No markdown, no extra text, just JSON.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.7,
            'max_tokens' => $tokenLimit,
            'response_format' => ['type' => 'json_object'],
        ]);

        if (!$response->successful()) {
            $error = $response->json()['error']['message'] ?? $response->body();
            throw new Exception('OpenAI API Error: ' . $error);
        }

        $content = $response->json()['choices'][0]['message']['content'] ?? '';

        Log::info('OpenAI quiz generation response', [
            'content_length' => strlen($content),
            'content_preview' => substr($content, 0, 200),
        ]);

        return $this->sanitizeString($content);
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
            ])->connectTimeout(8)->timeout(35);

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
            ])->connectTimeout(8)->timeout(35);

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
            ])->connectTimeout(8)->timeout(35);

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
            ])->connectTimeout(8)->timeout(35);

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
