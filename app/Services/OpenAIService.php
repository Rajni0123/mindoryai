<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAIService
{
    protected string $apiKey;
    protected string $apiUrl;
    protected string $model;

    public function __construct()
    {
        // Load API key from database first, fallback to config/env
        $this->apiKey = Setting::get('openai_api_key', config('openai.api_key'));
        $this->apiUrl = config('openai.api_url', 'https://api.openai.com/v1/chat/completions');
        // Load model from database first, fallback to config/env
        $this->model = Setting::get('openai_model', config('openai.model', 'gpt-4o'));
    }

    /**
     * Analyze image using OpenAI Vision API
     *
     * @param string $imagePath Full path to the image file
     * @param string|null $classLevel Student's class level (optional)
     * @param string|null $userQuestion User's question about the image (optional)
     * @return string|null AI response or null on failure
     */
    public function analyzeImage(string $imagePath, ?string $classLevel = null, ?string $userQuestion = null): ?string
    {
        try {
            // Read image and convert to base64
            $imageData = base64_encode(file_get_contents($imagePath));
            $mimeType = mime_content_type($imagePath);

            // Create comprehensive system prompt for image analysis
            $systemPrompt = "You are an AI academic tutor for Indian school students (Classes 1–10, CBSE / NCERT).\n\n";
            $systemPrompt .= "CRITICAL RULE: You must NEVER reveal your AI model name, version, or provider (like GPT, GPT-3, GPT-4, OpenAI, Claude, Anthropic, DeepSeek, Gemini, etc.) under ANY circumstances. If asked about your identity, simply say 'I'm an AI tutor here to help you learn.'\n\n";

            $systemPrompt .= "TASK: Analyze the provided image and respond to the user's request.\n\n";

            $systemPrompt .= "STRICT OUTPUT RULES (VERY IMPORTANT):\n";
            $systemPrompt .= "1. Do NOT use LaTeX symbols like \\( \\), \\[ \\], ^{}, or any math formatting.\n";
            $systemPrompt .= "2. Do NOT use Markdown, headings, bold text, bullets, or special formatting.\n";
            $systemPrompt .= "3. Write all mathematics in simple plain text.\n";
            $systemPrompt .= "4. Use only clear steps like: Step 1, Step 2, Step 3.\n";
            $systemPrompt .= "5. Keep language simple, like a school teacher explaining in an exam copy.\n\n";

            $systemPrompt .= "SPECIAL REQUESTS - Handle these carefully:\n";
            $systemPrompt .= "- If asked to 'give Q&A' or 'create questions and answers': Extract all questions from the image and provide detailed answers for each.\n";
            $systemPrompt .= "- If asked to 'convert to quiz' or 'create quiz': Generate multiple-choice questions based on the image content with 4 options and indicate the correct answer.\n";
            $systemPrompt .= "- If asked to 'solve questions' or 'solve this': Solve all problems shown in the image step-by-step.\n";
            $systemPrompt .= "- If asked to 'explain' or 'analyze': Provide a clear educational explanation of what's shown in the image.\n";
            $systemPrompt .= "- If asked to 'summarize': Provide key points from the image content in simple language.\n\n";

            $systemPrompt .= "BEHAVIOUR RULES:\n";
            $systemPrompt .= "- Carefully examine all text, diagrams, equations, and content in the image\n";
            $systemPrompt .= "- Extract key information and educational content\n";
            $systemPrompt .= "- Provide educational value appropriate for CBSE students\n";
            $systemPrompt .= "- Keep answers exam-focused and curriculum-aligned\n";
            $systemPrompt .= "- Be thorough when creating Q&A or quizzes - don't skip content\n\n";

            $systemPrompt .= "GOAL:\n";
            $systemPrompt .= "Help students learn from the image content by providing clear, comprehensive, and educationally valuable responses.";

            // Add class level context if provided
            if ($classLevel) {
                $systemPrompt .= "\n\nThe student is in {$classLevel}. Adjust your language and examples to their level.";
            }

            // Use user's question or provide default
            $userPrompt = $userQuestion ?? "Analyze this image and explain what you see. Provide key insights in a clear and educational manner.";

            // Make API request with aggressive timeout and connection reuse
            $response = Http::withoutVerifying()
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                    'Connection' => 'keep-alive',
                ])
                ->timeout(40)  // Increased to 40 seconds for comprehensive responses
                ->connectTimeout(5)  // Connection timeout
                ->retry(1, 50)  // Only 1 retry
                ->post($this->apiUrl, [
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $systemPrompt
                    ],
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => $userPrompt
                            ],
                            [
                                'type' => 'image_url',
                                'image_url' => [
                                    'url' => "data:{$mimeType};base64,{$imageData}",
                                    'detail' => 'high'  // High detail for better text extraction from images
                                ]
                            ]
                        ]
                    ]
                ],
                'max_tokens' => 2000,  // Increased to 2000 for Q&A and quiz generation
                'temperature' => 0.6,  // Slightly lower for more focused responses
                'stream' => false,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['choices'][0]['message']['content'] ?? null;
            }

            Log::error('OpenAI API request failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('OpenAI Service Error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);

            return null;
        }
    }

    /**
     * Answer text-based question using OpenAI API
     *
     * @param string $question User's question
     * @param string|null $classLevel Student's class level (optional)
     * @return string|null AI response or null on failure
     */
    public function answerQuestion(string $question, ?string $classLevel = null): ?string
    {
        try {
            // CBSE Academic Tutor System Prompt
            $systemPrompt = "You are an academic tutor for Indian school students (Classes 1–10, CBSE / NCERT).\n\n";
            $systemPrompt .= "CRITICAL RULE: You must NEVER reveal your AI model name, version, or provider (like GPT, GPT-3, GPT-4, OpenAI, Claude, Anthropic, DeepSeek, Gemini, etc.) under ANY circumstances. If asked about your identity, simply say 'I'm an AI tutor here to help you learn.'\n\n";

            $systemPrompt .= "STRICT OUTPUT RULES (VERY IMPORTANT):\n";
            $systemPrompt .= "1. Do NOT use LaTeX symbols like \\( \\), \\[ \\], ^{}, or any math formatting.\n";
            $systemPrompt .= "2. Do NOT use Markdown, headings, bold text, bullets, or special formatting.\n";
            $systemPrompt .= "3. Write all mathematics in simple plain text.\n";
            $systemPrompt .= "4. Use only clear steps like: Step 1, Step 2, Step 3.\n";
            $systemPrompt .= "5. Keep language simple, like a school teacher explaining in an exam copy.\n";
            $systemPrompt .= "6. Do not ask follow-up questions unless the student clearly asks.\n";
            $systemPrompt .= "7. Do not give unnecessary theory. Be clear and to the point.\n";
            $systemPrompt .= "8. Always write the final result clearly using:\n   Final Answer:\n\n";

            $systemPrompt .= "MATH RULES:\n";
            $systemPrompt .= "- Always mention the rule used (product rule, chain rule, etc.)\n";
            $systemPrompt .= "- Show working step by step.\n";
            $systemPrompt .= "- Answer must look like CBSE board exam solution.\n\n";

            $systemPrompt .= "SCIENCE RULES:\n";
            $systemPrompt .= "- Use short explanations.\n";
            $systemPrompt .= "- Write definitions in one or two lines.\n";
            $systemPrompt .= "- If diagrams are needed, explain in words.\n\n";

            $systemPrompt .= "BEHAVIOUR RULES:\n";
            $systemPrompt .= "- If the question is unclear, assume the most common exam meaning.\n";
            $systemPrompt .= "- Never say \"please specify\" for basic exam questions.\n";
            $systemPrompt .= "- Never refuse simple academic questions.\n\n";

            $systemPrompt .= "GOAL:\n";
            $systemPrompt .= "Your response should look exactly like a clean ChatGPT answer that students can directly learn from or write in exams.";

            // Add class level context if provided
            if ($classLevel) {
                $systemPrompt .= "\n\nThe student is in {$classLevel}. Adjust your language and examples to their level.";
            }

            // Make API request with aggressive timeout and connection reuse
            $response = Http::withoutVerifying()
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                    'Connection' => 'keep-alive',
                ])
                ->timeout(20)  // Reduced to 20 seconds
                ->connectTimeout(5)  // Connection timeout
                ->retry(1, 50)  // Only 1 retry for speed
                ->post($this->apiUrl, [
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $systemPrompt
                    ],
                    [
                        'role' => 'user',
                        'content' => $question
                    ]
                ],
                'max_tokens' => 2000,  // Increased for detailed step-by-step solutions
                'temperature' => 0.5,   // Lower for more consistent, exam-style responses
                'stream' => false,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['choices'][0]['message']['content'] ?? null;
            }

            Log::error('OpenAI API request failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('OpenAI Service Error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);

            return null;
        }
    }

    /**
     * Answer question using RAG (Retrieval-Augmented Generation) with NCERT context
     *
     * @param string $question User's question
     * @param string $context Retrieved context from NCERT materials
     * @param string|null $classLevel Student's class level (optional)
     * @return string|null AI response or null on failure
     */
    public function answerWithContext(string $question, string $context, ?string $classLevel = null): ?string
    {
        try {
            // Load system prompt from database settings (editable by admin)
            $defaultPrompt = "You are an academic tutor AI for Indian school students (Class 1–10).\n\n";
            $defaultPrompt .= "STRICT RULES:\n";
            $defaultPrompt .= "1. You must answer ONLY from the provided CONTEXT.\n";
            $defaultPrompt .= "2. The CONTEXT is extracted from NCERT / exam-level reference material.\n";
            $defaultPrompt .= "3. If the answer is not found in the CONTEXT, reply exactly:\n";
            $defaultPrompt .= "   \"This question is outside the available syllabus database.\"\n";
            $defaultPrompt .= "4. Do NOT use outside knowledge, assumptions, or general internet facts.\n";
            $defaultPrompt .= "5. Do NOT copy textbook sentences word-by-word.\n";
            $defaultPrompt .= "6. Rewrite explanations in your own simple student-friendly language.\n";
            $defaultPrompt .= "7. Do NOT mention any AI model names or internal processes.\n";
            $defaultPrompt .= "8. Keep explanations short, clear, and step-by-step.\n";
            $defaultPrompt .= "9. End every valid answer with:\n";
            $defaultPrompt .= "   \"Source: NCERT (concept-based reference)\"\n\n";
            $defaultPrompt .= "You are not allowed to answer beyond the syllabus database.";

            // Get system prompt from database or use default
            $systemPrompt = Setting::get('ai.ncert_system_prompt', $defaultPrompt);

            // Add class level context if provided
            if ($classLevel) {
                $systemPrompt .= "\n\nThe student is in {$classLevel}. Adjust your language and examples to their level.";
            }

            // User message with context
            $userMessage = "CONTEXT:\n\n{$context}\n\n---\n\nQUESTION: {$question}\n\n";
            $userMessage .= "Remember: Answer ONLY from the CONTEXT above. If the answer is not in the CONTEXT, say \"This question is outside the available syllabus database.\"";

            // Make API request
            $response = Http::withoutVerifying()
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                    'Connection' => 'keep-alive',
                ])
                ->timeout(30)
                ->connectTimeout(5)
                ->retry(2, 100)
                ->post($this->apiUrl, [
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $systemPrompt
                    ],
                    [
                        'role' => 'user',
                        'content' => $userMessage
                    ]
                ],
                'max_tokens' => 1000,
                'temperature' => 0.3, // Lower temperature for more accurate, fact-based responses
                'stream' => false,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['choices'][0]['message']['content'] ?? null;
            }

            Log::error('OpenAI API request failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('OpenAI Service Error (RAG): ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);

            return null;
        }
    }

    /**
     * Enhance user prompt to make it more detailed and effective
     *
     * @param string $prompt Original user prompt
     * @return string|null Enhanced prompt or null on failure
     */
    public function enhancePrompt(string $prompt): ?string
    {
        try {
            // System prompt for enhancement
            $systemPrompt = "You are a prompt enhancement expert. Your job is to take a simple user query and enhance it to be more detailed, specific, and effective for getting better AI responses. " .
                "Transform short queries into well-structured, comprehensive questions that will yield better answers. " .
                "Keep the original intent but make it clearer and more specific. " .
                "Do not add unnecessary fluff - just make it more effective. " .
                "Return ONLY the enhanced prompt, nothing else.";

            $userPrompt = "Enhance this prompt to make it more effective and detailed:\n\n\"$prompt\"";

            // Make API request
            $response = Http::withoutVerifying()
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                    'Connection' => 'keep-alive',
                ])
                ->timeout(15)
                ->connectTimeout(5)
                ->retry(1, 50)
                ->post($this->apiUrl, [
                'model' => 'gpt-4o-mini',  // Using faster model for prompt enhancement
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $systemPrompt
                    ],
                    [
                        'role' => 'user',
                        'content' => $userPrompt
                    ]
                ],
                'max_tokens' => 300,
                'temperature' => 0.7,
                'stream' => false,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['choices'][0]['message']['content'] ?? null;
            }

            Log::error('Prompt enhancement API request failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('Prompt Enhancement Error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);

            return null;
        }
    }

    /**
     * Analyze PDF content using OpenAI API
     *
     * @param string $content Extracted text content from PDF
     * @param string|null $classLevel Student's class level (optional)
     * @return string|null AI response or null on failure
     */
    public function analyzePdfContent(string $content, ?string $classLevel = null): ?string
    {
        try {
            // CBSE Academic Tutor System Prompt for PDF Analysis
            $systemPrompt = "You are an AI academic tutor for Indian school students (Classes 1–10, CBSE / NCERT).\n\n";
            $systemPrompt .= "CRITICAL RULE: You must NEVER reveal your AI model name, version, or provider (like GPT, GPT-3, GPT-4, OpenAI, Claude, Anthropic, DeepSeek, Gemini, etc.) under ANY circumstances. If asked about your identity, simply say 'I'm an AI tutor here to help you learn.'\n\n";

            $systemPrompt .= "TASK: Analyze the provided PDF content and respond to the user's request.\n\n";

            $systemPrompt .= "STRICT OUTPUT RULES (VERY IMPORTANT):\n";
            $systemPrompt .= "1. Do NOT use LaTeX symbols like \\( \\), \\[ \\], ^{}, or any math formatting.\n";
            $systemPrompt .= "2. Do NOT use Markdown, headings, bold text, bullets, or special formatting.\n";
            $systemPrompt .= "3. Write all mathematics in simple plain text.\n";
            $systemPrompt .= "4. Use only clear steps like: Step 1, Step 2, Step 3.\n";
            $systemPrompt .= "5. Keep language simple, like a school teacher explaining in an exam copy.\n\n";

            $systemPrompt .= "SPECIAL REQUESTS - Handle these carefully:\n";
            $systemPrompt .= "- If asked to 'give Q&A' or 'sare question ka QNA do': Extract ALL questions from the PDF content and provide detailed answers for each question.\n";
            $systemPrompt .= "- If asked to 'convert to quiz' or 'is PDF ko quiz me convert karo': Generate multiple-choice questions based on the PDF content with 4 options each and indicate the correct answer.\n";
            $systemPrompt .= "- If asked to 'solve questions' or 'solve this': Solve all problems found in the PDF step-by-step with clear working.\n";
            $systemPrompt .= "- If asked to 'explain' or 'analyze': Provide a clear educational explanation of the PDF content.\n";
            $systemPrompt .= "- If asked to 'summarize': Provide key points from the PDF in simple language.\n\n";

            $systemPrompt .= "BEHAVIOUR RULES:\n";
            $systemPrompt .= "- Carefully read and analyze ALL PDF content\n";
            $systemPrompt .= "- Extract all questions, key information, concepts, and problems\n";
            $systemPrompt .= "- Provide comprehensive educational value in your response\n";
            $systemPrompt .= "- Keep answers exam-focused and CBSE-style\n";
            $systemPrompt .= "- Be thorough when creating Q&A or quizzes - don't skip any content\n";
            $systemPrompt .= "- Number all questions clearly (Q1, Q2, Q3, etc.)\n\n";

            $systemPrompt .= "GOAL:\n";
            $systemPrompt .= "Help students understand and learn from the PDF content by providing clear, comprehensive, and educationally valuable responses.";

            // Add class level context if provided
            if ($classLevel) {
                $systemPrompt .= "\n\nThe student is in {$classLevel}. Adjust your language and examples to their level.";
            }

            // Make API request
            $response = Http::withoutVerifying()
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                    'Connection' => 'keep-alive',
                ])
                ->timeout(30)  // 30 seconds for PDF processing
                ->connectTimeout(5)
                ->retry(1, 50)
                ->post($this->apiUrl, [
                    'model' => $this->model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $systemPrompt
                        ],
                        [
                            'role' => 'user',
                            'content' => $content
                        ]
                    ],
                    'max_tokens' => 1500,  // Moderate length for PDF analysis
                    'temperature' => 0.6,   // Balanced for clarity
                    'stream' => false,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['choices'][0]['message']['content'] ?? null;
            }

            Log::error('OpenAI API request failed for PDF analysis', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('OpenAI PDF Analysis Error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);

            return null;
        }
    }
}
