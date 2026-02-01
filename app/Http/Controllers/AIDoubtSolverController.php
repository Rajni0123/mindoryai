<?php

namespace App\Http\Controllers;

use App\Models\DoubtCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AIDoubtSolverController extends Controller
{
    private $apiKey;
    private $apiUrl = 'https://api.openai.com/v1/chat/completions';

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
    }

    /**
     * Professional System Prompt for Student Doubt Solving
     */
    private function getSystemPrompt()
    {
        return <<<EOT
You are an expert AI tutor designed specifically for students. Your goal is to provide clear, educational, and well-structured answers.

🎯 **CORE PRINCIPLES:**
1. Always provide step-by-step explanations
2. Use proper academic formatting
3. Break down complex concepts into simple parts
4. Use examples and analogies
5. Be encouraging and supportive

📝 **FORMATTING STANDARDS:**

**For Science/Math Questions:**
- Start with a brief definition or overview
- List the main steps/concepts clearly
- Use numbered lists (1, 2, 3) for sequential processes
- Use bullet points (•) for key points or features
- Provide the formula or equation if applicable
- Add a summary or final note

**For Theory Questions:**
- Begin with a clear, direct answer
- Organize information in logical sections
- Use headings when covering multiple aspects
- Provide real-world examples or applications
- End with key takeaways

**Visual Structure Rules:**
- Use **bold** for important terms and concepts
- Use proper spacing between sections
- Keep paragraphs 2-4 sentences long
- Use line breaks for readability
- Add relevant emojis sparingly (💡, ✅, ⚠️) for engagement

**Language:**
- Use clear, simple English
- Explain technical terms when first used
- If student asks in Hindi/Hinglish, respond accordingly
- Maintain a friendly but professional tone

**Important:**
- Never be condescending
- Always encourage learning
- Provide complete, thorough explanations
- Make content easy to understand

Remember: You're helping students LEARN, not just giving answers.
EOT;
    }

    /**
     * Solve text-based doubt
     */
    public function solveTextDoubt(Request $request)
    {
        $request->validate([
            'question' => 'required|string',
            'subject' => 'nullable|string',
            'conversation_history' => 'nullable|array'
        ]);

        $question = $request->question;
        $subject = $request->subject;
        $conversationHistory = $request->conversation_history ?? [];

        // Build messages array
        $messages = [
            [
                'role' => 'system',
                'content' => $this->getSystemPrompt()
            ]
        ];

        // Add conversation history
        if (!empty($conversationHistory)) {
            $messages = array_merge($messages, $conversationHistory);
        }

        // Add current question
        $userMessage = $question;
        if ($subject) {
            $userMessage = "[Subject: {$subject}]\n\n{$question}";
        }

        $messages[] = [
            'role' => 'user',
            'content' => $userMessage
        ];

        try {
            // Make API call to OpenAI
            $http = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(60);

            // Disable SSL verification in local development
            if (app()->environment('local')) {
                $http = $http->withoutVerifying();
            }

            $response = $http->post($this->apiUrl, [
                'model' => 'gpt-4o-mini',
                'messages' => $messages,
                'temperature' => 0.7,
                'max_tokens' => 2500,
                'top_p' => 0.9,
                'frequency_penalty' => 0.3,
                'presence_penalty' => 0.3
            ]);

            if ($response->successful()) {
                $aiResponse = $response->json()['choices'][0]['message']['content'];

                return response()->json([
                    'success' => true,
                    'answer' => $aiResponse,
                    'tokens_used' => $response->json()['usage']['total_tokens'] ?? 0
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'API request failed: ' . $response->body()
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Solve image-based doubt
     */
    public function solveImageDoubt(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:10240',
            'question' => 'nullable|string',
            'subject' => 'nullable|string'
        ]);

        try {
            // Get uploaded image
            $image = $request->file('image');

            // Calculate image hash for cache lookup
            $imageHash = hash_file('sha256', $image->getRealPath());
            $questionText = $request->question;

            // Check cache
            $cached = DoubtCache::findCached('image', $imageHash, $questionText);
            if ($cached) {
                Log::info('Image doubt cache HIT', ['hash' => substr($imageHash, 0, 12), 'usage_count' => $cached->usage_count]);
                return response()->json([
                    'success' => true,
                    'answer' => $cached->solution,
                    'tokens_used' => $cached->tokens_used,
                    'cached' => true,
                ]);
            }

            // Convert image to base64
            $imageData = base64_encode(file_get_contents($image->getRealPath()));
            $mimeType = $image->getMimeType();

            // Build message content
            $textContent = "Please analyze this image and solve the problem/question shown in it. Provide a detailed, step-by-step solution.";

            if ($request->subject) {
                $textContent = "[Subject: {$request->subject}]\n\n" . $textContent;
            }

            if ($request->question) {
                $textContent .= "\n\nAdditional context: {$request->question}";
            }

            $messages = [
                [
                    'role' => 'system',
                    'content' => $this->getSystemPrompt()
                ],
                [
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
                ]
            ];

            // Make API call with vision model
            $http = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(90);

            // Disable SSL verification in local development
            if (app()->environment('local')) {
                $http = $http->withoutVerifying();
            }

            $response = $http->post($this->apiUrl, [
                'model' => 'gpt-4o-mini',
                'messages' => $messages,
                'max_tokens' => 2500,
                'temperature' => 0.7
            ]);

            if ($response->successful()) {
                $aiResponse = $response->json()['choices'][0]['message']['content'];
                $tokensUsed = $response->json()['usage']['total_tokens'] ?? 0;

                // Store in cache
                DoubtCache::storeSolution('image', $imageHash, $questionText, $request->subject, $aiResponse, $tokensUsed);
                Log::info('Image doubt cached', ['hash' => substr($imageHash, 0, 12)]);

                return response()->json([
                    'success' => true,
                    'answer' => $aiResponse,
                    'tokens_used' => $tokensUsed,
                    'cached' => false,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'API request failed: ' . $response->body()
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Solve PDF-based doubt
     */
    public function solvePdfDoubt(Request $request)
    {
        $request->validate([
            'pdf' => 'required|mimes:pdf|max:10240',
            'question' => 'nullable|string',
            'subject' => 'nullable|string'
        ]);

        try {
            $pdf = $request->file('pdf');

            // Check if PDF parser is available
            if (!class_exists('\Smalot\PdfParser\Parser')) {
                return response()->json([
                    'success' => false,
                    'error' => 'PDF parser not installed. Run: composer require smalot/pdfparser'
                ], 500);
            }

            // Extract text from PDF using a package like Smalot\PdfParser
            $parser = new \Smalot\PdfParser\Parser();
            $pdfParsed = $parser->parseFile($pdf->getRealPath());
            $pdfText = substr($pdfParsed->getText(), 0, 12000); // Limit text

            // Calculate content hash for cache lookup
            $contentHash = hash('sha256', $pdfText);
            $questionText = $request->question;

            // Check cache
            $cached = DoubtCache::findCached('pdf', $contentHash, $questionText);
            if ($cached) {
                Log::info('PDF doubt cache HIT', ['hash' => substr($contentHash, 0, 12), 'usage_count' => $cached->usage_count]);
                return response()->json([
                    'success' => true,
                    'answer' => $cached->solution,
                    'cached' => true,
                ]);
            }

            $userMessage = "I have the following content from a PDF document:\n\n---\n{$pdfText}\n---\n\n";

            if ($request->subject) {
                $userMessage = "[Subject: {$request->subject}]\n\n" . $userMessage;
            }

            if ($request->question) {
                $userMessage .= "My question: {$request->question}";
            } else {
                $userMessage .= "Please explain the key concepts from this content in a clear and structured way.";
            }

            $messages = [
                [
                    'role' => 'system',
                    'content' => $this->getSystemPrompt()
                ],
                [
                    'role' => 'user',
                    'content' => $userMessage
                ]
            ];

            $http = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(90);

            // Disable SSL verification in local development
            if (app()->environment('local')) {
                $http = $http->withoutVerifying();
            }

            $response = $http->post($this->apiUrl, [
                'model' => 'gpt-4o-mini',
                'messages' => $messages,
                'temperature' => 0.7,
                'max_tokens' => 2500
            ]);

            if ($response->successful()) {
                $aiResponse = $response->json()['choices'][0]['message']['content'];
                $tokensUsed = $response->json()['usage']['total_tokens'] ?? 0;

                // Store in cache
                DoubtCache::storeSolution('pdf', $contentHash, $questionText, $request->subject, $aiResponse, $tokensUsed);
                Log::info('PDF doubt cached', ['hash' => substr($contentHash, 0, 12)]);

                return response()->json([
                    'success' => true,
                    'answer' => $aiResponse,
                    'cached' => false,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'API request failed'
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
