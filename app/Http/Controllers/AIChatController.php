<?php

namespace App\Http\Controllers;

use App\Services\UnifiedAIService;
use App\Services\UsageLimitService;
use App\Services\LearningAnalyticsService;

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

        // Detect language from user's message
        $detectedLanguage = $this->detectLanguage($request->message ?? '');

        // Track language preference if user is logged in
        if ($user) {
            LearningAnalyticsService::detectAndTrackLanguage($user->id, $request->message ?? '');
        }

        // Build the BlinkStudy system prompt (different for premium vs free, and language-aware)
        $systemPrompt = $this->buildSystemPrompt($isPremium, $detectedLanguage);

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

        // Use hinglish as default for file uploads (since no text message to detect from)
        $systemPrompt = $this->buildSystemPrompt(false, 'hinglish');

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
     * Detect language from user's message
     * Returns: 'hindi' (Devanagari), 'hinglish' (Roman Hindi), or 'english'
     */
    private function detectLanguage(string $message): string
    {
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

    /**
     * Build the BlinkStudy system prompt
     * Premium users get direct detailed answers without asking for confirmation
     * Language-aware: responds in same language as user's message
     */
    private function buildSystemPrompt($isPremium = false, $language = 'hinglish')
    {
        // Language-specific instructions
        $languageInstruction = match ($language) {
            'hindi' => "- Language: PURE HINDI (हिन्दी) - Use Devanagari script ONLY
- तुम्हें हिंदी में ही जवाब देना है (देवनागरी लिपि में)
- English शब्दों का उपयोग केवल technical terms के लिए करो जिनका Hindi में कोई equivalent नहीं है
- Example response: \"यह एक बहुत अच्छा सवाल है! आइए इसे समझते हैं...\"",
            'hinglish' => "- Language: HINGLISH (Hindi + English mix in Roman script)
- Mix Hindi and English naturally like Indian students speak
- Example: \"Yeh bahut accha question hai! Let me explain...\"",
            default => "- Language: ENGLISH with occasional Hindi words
- Respond primarily in English
- You can use common Hindi words like 'samjho', 'dekho' for connection",
        };

        $basePrompt = "You are **Blinky** - the fun, friendly AI Tutor from BlinkStudy! <icon name=\"bulb\"/>

🎭 YOUR PERSONALITY:
- You're like that cool senior/bhaiya who makes studying fun!
- Enthusiastic, encouraging, slightly playful
- You celebrate small wins: \"Bahut badhiya! 🎉\", \"Sahi jawab! <icon name=\"tick\"/>\"
- You use relatable Indian examples (cricket, movies, food, daily life)
- You're patient - never make students feel dumb

{$languageInstruction}

📝 RESPONSE STYLE - VERY IMPORTANT:
You MUST use visual icons to make explanations engaging!

**ICON SYNTAX:** Use <icon name=\"xyz\"/> inline in your text.

**AVAILABLE ICONS (USE THESE!):**
Physics: <icon name=\"sun\"/> <icon name=\"atom\"/> <icon name=\"magnet\"/> <icon name=\"wave\"/> <icon name=\"battery\"/> <icon name=\"energy\"/>
Chemistry: <icon name=\"flask\"/> <icon name=\"molecule\"/> <icon name=\"water\"/> <icon name=\"fire\"/> <icon name=\"crystal\"/>
Biology: <icon name=\"dna\"/> <icon name=\"cell\"/> <icon name=\"heart\"/> <icon name=\"plant\"/> <icon name=\"brain\"/> <icon name=\"leaf\"/>
Maths: <icon name=\"graph\"/> <icon name=\"triangle\"/> <icon name=\"formula\"/> <icon name=\"calculator\"/> <icon name=\"infinity\"/>
General: <icon name=\"bulb\"/> <icon name=\"star\"/> <icon name=\"warning\"/> <icon name=\"tick\"/> <icon name=\"cross\"/> <icon name=\"arrow\"/>

**EXAMPLE RESPONSE (FOLLOW THIS FORMAT!):**

**Photosynthesis** <icon name=\"leaf\"/>

Socho plants apna khana kaise banate hain? <icon name=\"bulb\"/>

**Simple Formula:**
Sunlight <icon name=\"sun\"/> + Water <icon name=\"water\"/> + CO₂ → Glucose + Oxygen

**Step-by-step:**
1. <icon name=\"sun\"/> Sunlight leaves pe padti hai
2. <icon name=\"leaf\"/> Chlorophyll light absorb karta hai
3. <icon name=\"water\"/> Roots se water aata hai
4. <icon name=\"energy\"/> Energy se glucose banta hai!

**Yaad rakho:** <icon name=\"star\"/>
• Chlorophyll = Green color = Light absorber
• Photosynthesis sirf din mein hoti hai (sunlight chahiye!)

Samajh aaya? Koi doubt ho toh poocho! <icon name=\"bulb\"/>

---

**FORMATTING RULES:**
- Use **bold** for headings and key terms
- Use <icon name=\"xyz\"/> for visual elements (MINIMUM 3-5 icons per response!)
- Use • for bullet points
- Use 1. 2. 3. for steps
- Keep paragraphs short (mobile-friendly)
- Use Unicode: ², ³, ₂, →, ≈, √, ∑, ×, ÷

**TEACHING APPROACH:**
- Start with a hook/interesting fact
- Use analogies from daily life (\"Socho jaise...\")
- Break complex topics into small chunks
- End with a quick summary or memory trick
- Encourage questions: \"Kuch aur poochna hai?\"

**YOUR CATCHPHRASES:**
- \"Bahut easy hai, dekho...\"
- \"Ek simple trick batata hoon...\"
- \"Exam mein ye zaroor aata hai!\"
- \"Real life example dekho...\"
- \"Yaad karne ka shortcut...\"";

        // Add language matching rule
        $basePrompt .= "

⚠️ LANGUAGE RULE: Match the student's language!
- Hindi (देवनागरी) → Reply in Hindi
- Hinglish (Roman) → Reply in Hinglish
- English → Reply in English";

        // PREMIUM USERS: Full detailed tutoring
        if ($isPremium) {
            $basePrompt .= "

⭐ **PREMIUM STUDENT** - VIP Treatment!
- Give FULL, DETAILED explanations directly
- No need to ask \"detail mein samjhau?\"
- Include: concept + formula + example + exam tips
- Use MORE icons (5-7 per response) <icon name=\"star\"/>
- Add practice questions at the end
- Be extra encouraging!

**Response Structure for Premium:**
1. <icon name=\"bulb\"/> **Quick Hook** - interesting fact
2. <icon name=\"formula\"/> **Main Concept** - detailed explanation with icons
3. <icon name=\"tick\"/> **Key Points** - bullet summary
4. <icon name=\"star\"/> **Exam Tip** - what examiners look for
5. <icon name=\"calculator\"/> **Practice** - try this question!";
        } else {
            // FREE USERS: Engaging short answer, then offer more
            $basePrompt .= "

📚 **FREE STUDENT** - Response Flow:
1. Give a SHORT but ENGAGING answer (3-5 lines)
2. Include 2-3 icons to make it visual
3. End with: \"Detail mein samjhna hai? Bol 'haan' ya 'explain'! <icon name=\"bulb\"/>\"
4. If they say yes → Give FULL explanation with 5+ icons

**Short Answer Example:**
\"<icon name=\"atom\"/> **Atom** basically matter ki sabse chhoti unit hai!
Isme protons (+), neutrons (0), aur electrons (-) hote hain.
Socho jaise solar system - nucleus sun hai, electrons planets!

Detail mein samjhna hai? <icon name=\"bulb\"/>\"";
        }

        $basePrompt .= "

🎯 **REMEMBER:**
- You are BLINKY - the friendly tutor, NOT a boring textbook!
- ALWAYS use icons - they make learning visual and fun
- Keep energy high, make students excited to learn
- Use Indian context and examples
- Be like that helpful friend who makes studies easy!";

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

        // Check usage limits
        $user = auth()->user();
        if ($user) {
            $limitCheck = $this->usageLimitService->canUse($user, 'chat');
            if (!$limitCheck['allowed']) {
                return response()->json([
                    'success' => false,
                    'message' => $limitCheck['reason'],
                    'limit_reached' => true,
                ], 429);
            }
        }

        $mode = $request->mode ?? 'short';
        $prompt = "Class {$request->class} Math Question: {$request->question}";

        $systemPrompt = $this->buildSystemPrompt() . "\n\nThis is a MATHEMATICS question. Follow math-specific rules.";

        $response = $this->getAIResponse($prompt, $systemPrompt, [], $mode);

        // Record usage
        if ($user) {
            $this->usageLimitService->recordUsage($user, 'chat');
        }

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

        // Check usage limits
        $user = auth()->user();
        if ($user) {
            $limitCheck = $this->usageLimitService->canUse($user, 'chat');
            if (!$limitCheck['allowed']) {
                return response()->json([
                    'success' => false,
                    'message' => $limitCheck['reason'],
                    'limit_reached' => true,
                ], 429);
            }
        }

        $mode = $request->mode ?? 'short';
        $prompt = "Class {$request->class} Science Question: {$request->question}";

        $systemPrompt = $this->buildSystemPrompt() . "\n\nThis is a SCIENCE question. Follow science-specific rules.";

        $response = $this->getAIResponse($prompt, $systemPrompt, [], $mode);

        // Record usage
        if ($user) {
            $this->usageLimitService->recordUsage($user, 'chat');
        }

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

        // Check usage limits
        $user = auth()->user();
        if ($user) {
            $limitCheck = $this->usageLimitService->canUse($user, 'chat');
            if (!$limitCheck['allowed']) {
                return response()->json([
                    'success' => false,
                    'message' => $limitCheck['reason'],
                    'limit_reached' => true,
                ], 429);
            }
        }

        $count = $request->count ?? 5;
        $prompt = "Generate {$count} MCQs on topic: {$request->topic} for Class {$request->class} {$request->subject}. Include correct answer with explanation.";

        $systemPrompt = $this->buildSystemPrompt() . "\n\nGenerate exam-oriented MCQs with detailed explanations.";

        $response = $this->getAIResponse($prompt, $systemPrompt, [], 'detail');

        // Record usage
        if ($user) {
            $this->usageLimitService->recordUsage($user, 'chat');
        }

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

        // Check usage limits
        $user = auth()->user();
        if ($user) {
            $limitCheck = $this->usageLimitService->canUse($user, 'chat');
            if (!$limitCheck['allowed']) {
                return response()->json([
                    'success' => false,
                    'message' => $limitCheck['reason'],
                    'limit_reached' => true,
                ], 429);
            }
        }

        $prompt = "Create detailed revision notes on: {$request->topic} for Class {$request->class} {$request->subject}. Make it exam-focused.";

        $systemPrompt = $this->buildSystemPrompt() . "\n\nCreate comprehensive, exam-oriented notes.";

        $response = $this->getAIResponse($prompt, $systemPrompt, [], 'detail');

        // Record usage
        if ($user) {
            $this->usageLimitService->recordUsage($user, 'chat');
        }

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

        // Check usage limits BEFORE processing
        if ($user) {
            $usageLimitService = app(\App\Services\UsageLimitService::class);
            $limitCheck = $usageLimitService->canUse($user, 'scan_solve');
            if (!$limitCheck['allowed']) {
                return response()->json([
                    'success' => false,
                    'message' => $limitCheck['reason'],
                    'limit_exceeded' => true,
                    'used' => $limitCheck['used'],
                    'limit' => $limitCheck['limit'],
                ], 429);
            }
        }

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

            // Extract base64 from data URI
            $imageBase64 = explode(',', $imageData['uri'])[1];

            // Speed-optimized options based on plan
            $maxTokens = match($planSlug) {
                'ultimate' => 4096,
                'pro' => 3072,
                'starter' => 2048,
                default => 1536,
            };

            $response = $this->solveImageWithGemini(
                imageBase64: $imageBase64,
                mimeType: $imageData['type'],
                prompt: $prompt,
                userId: $user?->id,
                maxTokens: $maxTokens,
                timeout: $isPremium ? 45 : 30,
            );

            // Record usage after successful processing
            if ($user) {
                $usageLimitService = app(\App\Services\UsageLimitService::class);
                $usageLimitService->recordUsage($user, 'scan_solve');
            }

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
            try {
                $systemPrompt = $this->buildSolveSystemPrompt();
                $prompt = $this->buildFastSolvePrompt($action, $fileExtension);
                $response = $this->getAIResponse($prompt, $systemPrompt, [], 'detail', $uploadedFile);

                if ($this->isAiServiceErrorResponse($response)) {
                    throw new \Exception('AI fallback returned service error');
                }

                // CRITICAL: Validate fallback response is not empty/error
                if (empty($response) || strlen($response) < 50) {
                    throw new \Exception('Fallback returned empty or invalid response');
                }

                // Check if response contains error indicators
                $errorIndicators = ['error:', 'failed', 'timeout', 'unable to process'];
                $responseLower = strtolower($response);
                foreach ($errorIndicators as $indicator) {
                    if (str_contains($responseLower, $indicator) && strlen($response) < 200) {
                        throw new \Exception('Fallback returned error response: ' . substr($response, 0, 100));
                    }
                }

                // Record usage after successful fallback processing
                if ($user) {
                    $usageLimitService = app(\App\Services\UsageLimitService::class);
                    $usageLimitService->recordUsage($user, 'scan_solve');
                }

                return response()->json([
                    'success' => true,
                    'response' => $response,
                    'action' => $action,
                    'file_type' => $fileExtension,
                    'fallback_mode' => true,
                ]);
            } catch (\Exception $fallbackError) {
                Log::error('Scan/Solve Fallback Also Failed', [
                    'primary_error' => $e->getMessage(),
                    'fallback_error' => $fallbackError->getMessage(),
                ]);

                // Return proper error response so frontend can show message
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to process image. Please try again with a clearer image or try later.',
                    'error_code' => 'SCAN_SOLVE_FAILED',
                    'action' => $action,
                ], 500);
            }
        }
    }

    /**
     * Try multiple Gemini vision models for scan/solve reliability.
     */
    private function solveImageWithGemini(
        string $imageBase64,
        string $mimeType,
        string $prompt,
        ?int $userId,
        int $maxTokens,
        int $timeout,
    ): array {
        $models = $this->getScanSolveGeminiModels();
        $lastError = null;

        foreach ($models as $modelName) {
            try {
                $geminiService = new \App\Services\GeminiService(
                    feature: 'scan_solve',
                    modelName: $modelName,
                    userId: $userId
                );

                $response = $geminiService->generateContentWithVision(
                    userPrompt: $prompt,
                    imageData: $imageBase64,
                    mimeType: $mimeType,
                    options: [
                        'temperature' => 0.3,
                        'maxOutputTokens' => $maxTokens,
                        'timeout' => $timeout,
                    ]
                );

                if (!empty(trim($response['content'] ?? ''))) {
                    return $response;
                }
            } catch (\Exception $e) {
                $lastError = $e;
                Log::warning('Scan solve Gemini model failed', [
                    'model' => $modelName,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        throw new \Exception($lastError?->getMessage() ?? 'Gemini vision failed for all models');
    }

    /**
     * @return list<string>
     */
    private function getScanSolveGeminiModels(): array
    {
        $fromDb = \App\Models\AiModel::where('provider', 'google')
            ->where('is_active', true)
            ->orderBy('order')
            ->pluck('model_identifier')
            ->filter()
            ->values()
            ->all();

        $defaults = [
            'gemini-2.0-flash',
            'gemini-1.5-flash',
            'gemini-1.5-flash-8b',
            'gemini-1.5-pro',
        ];

        return array_values(array_unique(array_merge($fromDb, $defaults)));
    }

    private function isAiServiceErrorResponse(string $response): bool
    {
        $lower = strtolower(trim($response));
        if ($lower === '') {
            return true;
        }

        $needles = [
            'all ai services are currently unavailable',
            'ai service unavailable',
            'ai service temporarily unavailable',
            'gemini api key not configured',
            'service temporarily unavailable',
            'please try again in a few moments',
        ];

        foreach ($needles as $needle) {
            if (str_contains($lower, $needle)) {
                return true;
            }
        }

        return str_starts_with($lower, 'error:');
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
