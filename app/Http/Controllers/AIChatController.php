<?php

namespace App\Http\Controllers;

use App\Services\UnifiedAIService;
use App\Services\UsageLimitService;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AIChatController extends Controller
{
    private UnifiedAIService $aiService;
    private UsageLimitService $usageLimitService;

    public function __construct(UnifiedAIService $aiService, UsageLimitService $usageLimitService)
    {
        $this->aiService = $aiService;
        $this->usageLimitService = $usageLimitService;
    }

    /**
     * Main chat handler - handles all student queries
     * Premium users get full detailed answers without asking
     */
    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'nullable|string',
            'file' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:10240',
            'image' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:10240',
            'conversation_history' => 'nullable|array',
            'mode' => 'nullable|in:short,detail',
            'subject' => 'nullable|string',
            'class' => 'nullable|string',
        ]);

        $user = auth()->user();

        // Check usage limits before proceeding
        if ($user) {
            $limitCheck = $this->usageLimitService->canUse($user, 'chat');
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

        $isPremium = $this->isPremiumUser($user);
        $userPriority = $this->getUserPriority($user);

        // Get file from either 'file' or 'image' parameter
        $uploadedFile = $request->file('file') ?? $request->file('image');

        // Check if file uploaded without message
        if ($uploadedFile && empty($request->message)) {
            return $this->handleFileUpload($request, $uploadedFile);
        }

        // Premium users ALWAYS get detail mode
        // Free users: Check if they want detail explanation
        $mode = $isPremium ? 'detail' : $this->detectMode($request->message, $request->mode);

        // Get conversation context
        $conversationHistory = $request->conversation_history ?? [];

        // Build the BlinkStudy system prompt (different for premium vs free)
        $systemPrompt = $this->buildSystemPrompt($isPremium);

        // Check if chat should be throttled (only for free/lite users)
        $throttleDelay = 0;
        if ($user && !$isPremium) {
            $throttleDelay = $this->usageLimitService->shouldThrottleChat($user);
        }

        // Log priority for debugging
        Log::info('Chat request priority', [
            'user_id' => $user?->id,
            'plan' => $user?->userPlan?->slug ?? 'free',
            'priority' => $userPriority,
            'is_premium' => $isPremium,
        ]);

        // Process the message
        $response = $this->getAIResponse(
            $request->message,
            $systemPrompt,
            $conversationHistory,
            $mode,
            $uploadedFile
        );

        // Record usage after successful response
        if ($user) {
            $this->usageLimitService->recordUsage($user, 'chat');
        }

        return response()->json([
            'success' => true,
            'response' => $response,
            'mode' => $mode,
            'throttled' => $throttleDelay > 0,
            'priority' => $userPriority,
            'is_premium' => $isPremium,
        ]);
    }

    /**
     * Handle file upload without message
     */
    private function handleFileUpload(Request $request, $file)
    {
        $fileExtension = $file->getClientOriginalExtension();
        $analysisPrompt = "";

        if ($fileExtension === 'pdf') {
            $analysisPrompt = "A PDF document has been uploaded. Analyze the content and identify what type of educational material this is (questions, notes, textbook page, etc.).";
        }

        if (in_array($file->extension(), ['jpg', 'png', 'jpeg'])) {
            $analysisPrompt = "An image has been uploaded. Analyze the content and identify what is shown (math problems, science diagrams, text notes, questions, etc.).";
        }

        $systemPrompt = $this->buildSystemPrompt();

        $response = $this->getAIResponse(
            $analysisPrompt,
            $systemPrompt,
            [],
            'short',
            $file
        );

        $responseText = $response . "\n\nKya aap chahte ho main:\na) Explain karu\nb) Questions solve karu\nc) Summary batau\nd) Notes bana du\ne) MCQs bana du";

        return response()->json([
            'success' => true,
            'response' => $responseText,
            'mode' => 'file_upload',
            'file_type' => $fileExtension,
            'awaiting_choice' => true,
        ]);
    }

    /**
     * Detect if user wants short or detail mode
     */
    private function detectMode($message, $requestedMode = null)
    {
        if ($requestedMode) {
            return $requestedMode;
        }

        $message = strtolower($message ?? '');

        $detailKeywords = ['yes', 'ha', 'haan', 'batao', 'batav', 'explain', 'detail', 'samjhao'];

        foreach ($detailKeywords as $keyword) {
            if (strpos($message, $keyword) !== false) {
                return 'detail';
            }
        }

        return 'short';
    }

    /**
     * Build the BlinkStudy system prompt
     * Premium users get direct detailed answers without asking for confirmation
     */
    private function buildSystemPrompt($isPremium = false)
    {
        $basePrompt = "You are BlinkStudy - a friendly educational AI assistant for Indian students. You love helping students learn!

🎓 YOUR ROLE:
- Help with education, study, learning, and academic questions
- Be friendly, encouraging, and supportive
- When users ask non-educational questions, gently guide them back to study topics
- NEVER refuse to answer - always be helpful and find an educational angle

✅ TOPICS YOU LOVE:
- Academic subjects (Math, Science, History, Geography, etc.)
- Study material, homework, exam preparation
- NCERT/CBSE syllabus questions
- Educational concepts and explanations
- Career guidance related to education
- Learning techniques and study methods
- Technical/programming education for students

💡 YOUR APPROACH FOR NON-EDUCATIONAL QUESTIONS:
Instead of refusing, find a creative way to connect it to learning:
- Movies/Entertainment → \"Bahut accha sawaal! Main study topics mein help karta hu. Kya aap [related educational concept] ke baare mein jaanna chahte ho?\"
- Games/Sports → \"Interesting! Main educational topics mein specialisation rakhta hu. Kya aap apne studies ya exams ke liye kuch seekhna chahte ho?\"
- Casual chat → Respond warmly, then guide: \"Hello! Main aapki studies mein help karne ke liye hu. Aaj aap kya seekhna chahte ho?\"

Your identity:
- Name: BlinkStudy
- Purpose: Help students with studies and learning
- Audience: Indian students (CBSE / NCERT focus)
- Language: Simple Hinglish (Hindi + English)

RESPONSE FORMATTING:
- Use plain text
- No markdown headers
- Use • for bullet points
- Keep mobile-friendly spacing
- Use Unicode for math: ², ³, ₂, →, ≈, √, ∑

TEACHING STYLE:
- Patient and motivating
- Simple Hinglish explanations
- NCERT terminology
- Exam-focused
- Never hallucinate
- Always helpful, guide gently to study topics";

        // PREMIUM USERS: Direct detailed answers without asking
        if ($isPremium) {
            $basePrompt .= "

⭐ PREMIUM USER - RESPONSE RULE:
This is a PREMIUM user. Give FULL, DETAILED, COMPREHENSIVE answers directly.
- DO NOT give short answers
- DO NOT ask \"Kya main detail me samjhau?\"
- Provide complete step-by-step solutions
- Include formulas, examples, and explanations
- Give exam-focused detailed content immediately

SUBJECT HANDLING (PREMIUM - ALWAYS DETAILED):

• Mathematics:
  - Full step-by-step solution
  - Formula with derivation
  - Similar example problems

• Science:
  - Complete concept explanation
  - Real-world examples
  - Important keywords for exams

• Social Science:
  - Detailed cause-effect analysis
  - Historical context
  - Important dates and facts

• English:
  - Full grammar rules with examples
  - Multiple usage examples
  - Common mistakes to avoid";
        } else {
            // FREE USERS: Short answer first, then ask for detail
            $basePrompt .= "

RESPONSE RULE (FREE USER):
1. FIRST give a SHORT, DIRECT ANSWER (2-5 lines max)
2. AFTER short answer, ALWAYS ask:
   \"Kya main isko detail me samjhau? (Yes / Ha / Batav likho)\"
3. ONLY IF user replies yes/ha/haan/batav/explain/detail, give FULL explanation
4. If user doesn't ask for detail, STOP after short answer

SUBJECT HANDLING:

• Mathematics:
  - Short: final answer
  - Detail: full steps, formulas, explanation

• Science:
  - Short: definition/key point
  - Detail: concept + example + keywords

• Social Science:
  - Short: main point
  - Detail: cause, effect, structured bullets

• English:
  - Short: rule or meaning
  - Detail: explanation + examples";
        }

        $basePrompt .= "

Remember: You are a HELPFUL EDUCATIONAL ASSISTANT. Always be helpful, guide gently to study topics, but never refuse to answer.";

        return $basePrompt;
    }

    /**
     * Check if user has a premium (paid) plan
     */
    private function isPremiumUser($user): bool
    {
        if (!$user) return false;

        $planSlug = strtolower($user->userPlan?->slug ?? '');
        return in_array($planSlug, ['ultimate', 'pro', 'starter', 'lite']);
    }

    /**
     * Get user's plan priority (higher = more priority)
     */
    private function getUserPriority($user): int
    {
        if (!$user) return 0;

        $planSlug = strtolower($user->userPlan?->slug ?? '');
        return match($planSlug) {
            'ultimate' => 100,
            'pro' => 75,
            'starter', 'lite' => 50,
            default => 10, // Free users
        };
    }

    /**
     * Get AI response using UnifiedAIService (uses admin panel active model)
     */
    private function getAIResponse($message, $systemPrompt, $conversationHistory, $mode, $file = null)
    {
        // Build conversation messages
        $messages = [];

        // Add system message
        $fullSystemPrompt = $systemPrompt;

        // Handle detail mode with conversation context
        if ($mode === 'detail' && !empty($conversationHistory)) {
            // Find the last user question (not yes/ha/haan etc)
            $lastTopic = null;
            $lastAssistantResponse = null;

            // Look backwards through history to find the original question
            for ($i = count($conversationHistory) - 1; $i >= 0; $i--) {
                $item = $conversationHistory[$i];
                if ($item['role'] === 'assistant' && !$lastAssistantResponse) {
                    $lastAssistantResponse = $item['content'];
                }
                if ($item['role'] === 'user') {
                    $userMsg = strtolower(trim($item['content']));
                    // Skip confirmation messages
                    if (!in_array($userMsg, ['yes', 'ha', 'haan', 'batao', 'batav', 'explain', 'detail', 'samjhao', 'ok', 'okay', 'ji', 'ji ha', 'ji haan'])) {
                        $lastTopic = $item['content'];
                        break;
                    }
                }
            }

            if ($lastTopic) {
                $fullSystemPrompt .= "\n\n⚠️ DETAIL MODE ACTIVATED:
The user previously asked about: \"{$lastTopic}\"
They said 'yes/ha' to get MORE DETAILS.

YOU MUST NOW:
1. Provide a FULL, COMPREHENSIVE, DETAILED explanation of: {$lastTopic}
2. Include step-by-step breakdown, examples, formulas if applicable
3. Cover all aspects thoroughly - this is what the student asked for
4. Do NOT give another short answer
5. Do NOT ask again if they want details - they already said YES

GIVE THE DETAILED EXPLANATION NOW.";
            } else {
                $fullSystemPrompt .= "\n\nUSER HAS REQUESTED DETAIL MODE. Provide full, comprehensive, step-by-step explanation based on the conversation context.";
            }
        } elseif ($mode === 'detail') {
            $fullSystemPrompt .= "\n\nUSER HAS REQUESTED DETAIL MODE. Provide full, comprehensive, step-by-step explanation now.";
        }

        $messages[] = [
            'role' => 'system',
            'content' => $fullSystemPrompt
        ];

        // Add conversation history
        foreach ($conversationHistory as $item) {
            $messages[] = [
                'role' => $item['role'],
                'content' => $item['content']
            ];
        }

        // Add current message
        $messages[] = [
            'role' => 'user',
            'content' => $message
        ];

        // Prepare image data if file is uploaded
        $imageData = null;
        if ($file) {
            $imageData = [
                'uri' => 'data:' . $file->getMimeType() . ';base64,' . base64_encode(file_get_contents($file->getRealPath())),
                'type' => $file->getMimeType(),
                'fileName' => $file->getClientOriginalName(),
            ];
        }

        try {
            // Use UnifiedAIService - automatically picks active model from admin panel
            $result = $this->aiService->chat(
                $message,
                null, // model ID - null means use first active model
                $messages,
                null, // no streaming
                $imageData,
                'blinkstudy_chat', // feature name for tracking
                auth()->id() // user ID for tracking
            );

            if ($result['success']) {
                return $result['content'];
            }

            Log::error('BlinkStudy AI Error', ['error' => $result['error'] ?? 'Unknown error']);
            return $result['error'] ?? 'AI service unavailable. Please try again later.';

        } catch (\Exception $e) {
            Log::error('BlinkStudy AI Exception', ['error' => $e->getMessage()]);
            return 'Error: ' . $e->getMessage();
        }
    }

    /**
     * Subject-specific handler for Mathematics
     */
    public function handleMathQuestion(Request $request)
    {
        $request->validate([
            'question' => 'required|string',
            'class' => 'required|string',
            'mode' => 'nullable|in:short,detail',
        ]);

        $mode = $request->mode ?? 'short';
        $prompt = "Class {$request->class} Math Question: {$request->question}";

        $systemPrompt = $this->buildSystemPrompt() . "\n\nThis is a MATHEMATICS question. Follow math-specific rules.";

        $response = $this->getAIResponse($prompt, $systemPrompt, [], $mode);

        return response()->json([
            'success' => true,
            'response' => $response,
            'subject' => 'mathematics',
        ]);
    }

    /**
     * Subject-specific handler for Science
     */
    public function handleScienceQuestion(Request $request)
    {
        $request->validate([
            'question' => 'required|string',
            'class' => 'required|string',
            'mode' => 'nullable|in:short,detail',
        ]);

        $mode = $request->mode ?? 'short';
        $prompt = "Class {$request->class} Science Question: {$request->question}";

        $systemPrompt = $this->buildSystemPrompt() . "\n\nThis is a SCIENCE question. Follow science-specific rules.";

        $response = $this->getAIResponse($prompt, $systemPrompt, [], $mode);

        return response()->json([
            'success' => true,
            'response' => $response,
            'subject' => 'science',
        ]);
    }

    /**
     * Generate MCQs from topic
     */
    public function generateMCQs(Request $request)
    {
        $request->validate([
            'topic' => 'required|string',
            'class' => 'required|string',
            'subject' => 'required|string',
            'count' => 'nullable|integer|min:1|max:20',
        ]);

        $count = $request->count ?? 5;
        $prompt = "Generate {$count} MCQs on topic: {$request->topic} for Class {$request->class} {$request->subject}. Include correct answer with explanation.";

        $systemPrompt = $this->buildSystemPrompt() . "\n\nGenerate exam-oriented MCQs with detailed explanations.";

        $response = $this->getAIResponse($prompt, $systemPrompt, [], 'detail');

        return response()->json([
            'success' => true,
            'response' => $response,
            'type' => 'mcqs',
        ]);
    }

    /**
     * Generate notes from topic
     */
    public function generateNotes(Request $request)
    {
        $request->validate([
            'topic' => 'required|string',
            'class' => 'required|string',
            'subject' => 'required|string',
        ]);

        $prompt = "Create detailed revision notes on: {$request->topic} for Class {$request->class} {$request->subject}. Make it exam-focused.";

        $systemPrompt = $this->buildSystemPrompt() . "\n\nCreate comprehensive, exam-oriented notes.";

        $response = $this->getAIResponse($prompt, $systemPrompt, [], 'detail');

        return response()->json([
            'success' => true,
            'response' => $response,
            'type' => 'notes',
        ]);
    }

    /**
     * Solve questions from image - OPTIMIZED FOR SPEED
     * Ultimate plan users get fastest processing with gemini-2.0-flash
     */
    public function solveFromImage(Request $request)
    {
        $request->validate([
            'file' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:10240',
            'image' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:10240',
            'action' => 'required|in:explain,solve,summary,notes,mcqs',
        ]);

        $uploadedFile = $request->file('file') ?? $request->file('image');

        if (!$uploadedFile) {
            return response()->json([
                'success' => false,
                'message' => 'File is required'
            ], 422);
        }

        $user = auth()->user();
        $fileExtension = $uploadedFile->getClientOriginalExtension();
        $action = $request->action;

        // Get plan-based speed optimization
        $planSlug = $user?->userPlan?->slug ?? 'free';
        $isPremium = in_array($planSlug, ['ultimate', 'pro', 'starter']);

        // Use FAST direct Gemini processing for scan/solve
        // Skip the heavy UnifiedAIService routing for speed
        try {
            // Prepare image data
            $imageData = [
                'uri' => 'data:' . $uploadedFile->getMimeType() . ';base64,' . base64_encode(file_get_contents($uploadedFile->getRealPath())),
                'type' => $uploadedFile->getMimeType(),
                'fileName' => $uploadedFile->getClientOriginalName(),
            ];

            // Build optimized prompt based on action
            $prompt = $this->buildFastSolvePrompt($action, $fileExtension);

            // Use GeminiService directly for SPEED (bypass UnifiedAIService routing)
            $geminiService = new \App\Services\GeminiService(
                feature: 'scan_solve',
                modelName: 'gemini-2.0-flash', // Always use fastest model
                userId: $user?->id
            );

            // Extract base64 from data URI
            $imageBase64 = explode(',', $imageData['uri'])[1];

            // Speed-optimized options based on plan
            $maxTokens = match($planSlug) {
                'ultimate' => 4096,  // Full detailed solutions
                'pro' => 3072,
                'starter' => 2048,
                default => 1536,
            };

            $response = $geminiService->generateContentWithVision(
                userPrompt: $prompt,
                imageData: $imageBase64,
                mimeType: $imageData['type'],
                options: [
                    'temperature' => 0.3,  // Lower = faster, more deterministic
                    'maxOutputTokens' => $maxTokens,
                    'timeout' => $isPremium ? 45 : 30,
                ]
            );

            return response()->json([
                'success' => true,
                'response' => $response['content'],
                'action' => $action,
                'file_type' => $fileExtension,
                'fast_mode' => true,
            ]);

        } catch (\Exception $e) {
            Log::error('Fast Scan/Solve Error', ['error' => $e->getMessage()]);

            // Fallback to regular method if fast mode fails
            $systemPrompt = $this->buildSolveSystemPrompt();
            $prompt = $this->buildFastSolvePrompt($action, $fileExtension);
            $response = $this->getAIResponse($prompt, $systemPrompt, [], 'detail', $uploadedFile);

            return response()->json([
                'success' => true,
                'response' => $response,
                'action' => $action,
                'file_type' => $fileExtension,
            ]);
        }
    }

    /**
     * Build optimized prompt for fast solving - shorter = faster
     */
    private function buildFastSolvePrompt(string $action, string $fileExtension): string
    {
        $prompts = [
            'solve' => "Solve ALL questions in this image. For each question:
• Write the question
• Show solution steps
• Give final answer

Be thorough but concise. Solve EVERY question visible.",

            'explain' => "Explain the educational content in this image clearly and concisely.",

            'summary' => "Summarize the key points from this image in bullet points.",

            'notes' => "Create concise revision notes from this image content.",

            'mcqs' => "Generate 5 MCQs based on this image content with answers.",
        ];

        return $prompts[$action] ?? $prompts['solve'];
    }

    /**
     * Build system prompt specifically for solving - gives DIRECT full solutions
     */
    private function buildSolveSystemPrompt()
    {
        return "You are BlinkStudy - an educational AI assistant. Solve questions from uploaded images/PDFs.

⚠️ CRITICAL RULES:
- SOLVE ALL QUESTIONS visible in the uploaded file - DO NOT skip any question
- DO NOT ask 'Kya main detail me samjhau?' or any confirmation
- DO NOT give short answers - give FULL solutions directly
- ONLY use content visible in the uploaded file
- If there are 5 questions, solve all 5. If there are 10, solve all 10.

📝 OUTPUT FORMAT (Follow EXACTLY for EACH and EVERY question):

* **[Question text here]**

* Answer: [For simple definition/concept questions, give direct answer]

OR (for math/numerical problems):

* **[Question text here]**

* Solution:

* We use [concept/formula name]: `formula here`

* Given: `values here`

* [Explanation of what we need to find]

* Rearrange/Apply formula: `formula step`

* Substitute values: `calculation`

* `final calculation = result`

* Answer: [Final answer in plain text]

FORMATTING RULES:
• Start each question with * **question text in bold**
• Use * for each bullet point line
• Put ALL formulas, equations, values inside backticks like `F = ma` or `10N / 2kg`
• For numbered lists (multiple examples/points), use: 1. **Label:** explanation
• Keep explanations in simple Hinglish
• NO section headers like 'QUESTIONS' or 'SOLUTIONS'
• Just go question by question with answer/solution immediately after each
• For MCQs: show correct option with explanation
• For fill-blanks: show the answer to fill

EXAMPLE OUTPUT:

* **Define Newton's second law of motion.**

* Answer: Newton's second law states that the acceleration of an object is directly proportional to the net force acting on it and inversely proportional to its mass. Mathematically, it's expressed as

`F = ma, where F is force, m is mass, and a is acceleration.`

* **If a force of 10N is applied to a mass of 2kg, what is the acceleration?**

* Solution:

* We use Newton's second law: `F = ma`

* Given: `F = 10N, m = 2kg`

* We need to find 'a' (acceleration).

* Rearrange the formula: `a = F/m`

* Substitute the values: `a = 10N / 2kg`

* `a = 5 m/s²`

* Answer: The acceleration is 5 meters per second squared.

* **Give three examples of Newton's third law in daily life.**

* Answer:

1. **Walking:** When you walk, you push backward on the ground (action), and the ground pushes you forward (reaction).

2. **Swimming:** When you swim, you push the water backward (action), and the water pushes you forward (reaction).

3. **Rocket Launch:** A rocket expels gases downward (action), and the gases exert an upward force on the rocket (reaction), propelling it into the sky.

⚠️ IMPORTANT REMINDER:
- You MUST solve EVERY SINGLE question visible in the image/PDF
- Do NOT stop after one or two questions
- Continue until ALL questions are solved with their complete solutions
- Each question should have * **Question** followed by its complete solution";
    }
}
