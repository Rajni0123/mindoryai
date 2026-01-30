<?php

namespace App\Services;

use App\Models\AiModel;
use App\Models\FrontendConfig;
use App\Models\AiUsageTracking;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UnifiedAIService
{
    /**
     * Send chat message to selected AI model with streaming support
     *
     * @param string $message User message
     * @param int|null $modelId Selected AI model ID (null = use first active)
     * @param array $conversationHistory Previous messages
     * @param callable|null $streamCallback Callback for streaming chunks
     * @param array|null $imageData Image data (uri, type, fileName)
     * @param string $feature Feature name (chat, quiz, etc.) for usage tracking
     * @param int|null $userId User ID for usage tracking
     * @return array Response with content and metadata
     */
    public function chat(string $message, ?int $modelId = null, array $conversationHistory = [], ?callable $streamCallback = null, ?array $imageData = null, string $feature = 'chat', ?int $userId = null): array
    {
        // STEP 1: Check for feature-specific AI model (highest priority)
        $featureModelId = $this->getFeatureSpecificModel($feature);
        $aiModel = null;

        // Get AI model from database
        if ($featureModelId) {
            // Feature-specific model has highest priority
            $aiModel = AiModel::where('id', $featureModelId)->where('is_active', true)->first();

            // Check if the feature model's provider is enabled
            if ($aiModel && !$this->isProviderEnabled($aiModel->provider)) {
                Log::warning("Feature-specific model's provider not enabled: {$aiModel->provider}", [
                    'feature' => $feature,
                    'model_id' => $featureModelId,
                ]);
                $aiModel = null; // Reset to try other options
            }
        }

        // STEP 2: If no feature-specific model, use user's selected model
        if (!$aiModel && $modelId) {
            $tempModel = AiModel::where('id', $modelId)->where('is_active', true)->first();
            // Only use if provider is enabled
            if ($tempModel && $this->isProviderEnabled($tempModel->provider)) {
                $aiModel = $tempModel;
            }
        }

        // STEP 3: If still no model, find first active model with ENABLED provider
        if (!$aiModel) {
            // Get all active models and find one with enabled provider
            $activeModels = AiModel::where('is_active', true)->orderBy('order')->get();

            foreach ($activeModels as $model) {
                if ($this->isProviderEnabled($model->provider)) {
                    $aiModel = $model;
                    Log::info("Auto-selected AI model with enabled provider", [
                        'model' => $model->name,
                        'provider' => $model->provider,
                    ]);
                    break;
                }
            }
        }

        // User can select any active model (GPT, Claude, Gemini, etc.)

        if (!$aiModel) {
            return [
                'success' => false,
                'error' => 'No AI model available. Please enable at least one AI provider (OpenAI, Gemini, Claude, or DeepSeek) in Admin Settings.',
            ];
        }

        // Double-check provider is enabled (should always pass now, but just in case)
        if (!$this->isProviderEnabled($aiModel->provider)) {
            return [
                'success' => false,
                'error' => ucfirst($aiModel->provider) . ' is not enabled. Please enable it in AI Settings.',
            ];
        }

        // Get API key from settings based on provider
        $apiKey = $this->getApiKey($aiModel->provider);

        if (!$apiKey) {
            return [
                'success' => false,
                'error' => ucfirst($aiModel->provider) . ' API key not configured. Please contact administrator.',
            ];
        }

        // Route to appropriate provider
        try {
            $result = match ($aiModel->provider) {
                'openai' => $this->sendToOpenAI($aiModel, $apiKey, $message, $conversationHistory, $streamCallback, $imageData),
                'anthropic' => $this->sendToClaude($aiModel, $apiKey, $message, $conversationHistory, $streamCallback, $imageData),
                'deepseek' => $this->sendToDeepSeek($aiModel, $apiKey, $message, $conversationHistory, $streamCallback, $imageData),
                'google' => $this->sendToGemini($aiModel, $apiKey, $message, $conversationHistory, $streamCallback, $imageData, $feature),
                default => [
                    'success' => false,
                    'error' => 'Unsupported AI provider: ' . $aiModel->provider,
                ],
            };

            // Track usage if successful and not streaming
            if ($result['success'] && !$streamCallback) {
                $this->trackUsage($result, $aiModel, $feature, $userId);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('AI Service Error', [
                'provider' => $aiModel->provider,
                'model' => $aiModel->model_identifier,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to communicate with AI service. Please try again.',
            ];
        }
    }

    /**
     * Check if provider is enabled in admin settings
     */
    protected function isProviderEnabled(string $provider): bool
    {
        $enabledKey = match ($provider) {
            'openai' => 'ai.openai_enabled',
            'anthropic' => 'ai.claude_enabled',
            'deepseek' => 'ai.deepseek_enabled',
            'google' => 'ai.gemini_enabled',
            'xai' => 'ai.grok_enabled',
            default => null,
        };

        if (!$enabledKey) {
            return false;
        }

        return FrontendConfig::getValue($enabledKey, '0') === '1';
    }

    /**
     * Get API key for provider from admin panel settings ONLY
     * No .env fallback - must be configured in admin panel
     */
    protected function getApiKey(string $provider): ?string
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

        // Only check from admin panel (FrontendConfig) - NO .env fallback
        $apiKey = FrontendConfig::getValue($key, '');

        return $apiKey ?: null;
    }

    /**
     * Send to OpenAI (GPT models)
     */
    protected function sendToOpenAI(AiModel $model, string $apiKey, string $message, array $history, ?callable $streamCallback, ?array $imageData = null): array
    {
        $messages = [];

        // Get system prompt
        $systemPrompt = $this->getSystemPrompt();

        // Add system prompt first (for all messages)
        if ($systemPrompt) {
            $messages[] = ['role' => 'system', 'content' => $systemPrompt];
        }

        // Add conversation history for continuity (important for follow-up questions)
        $messages = array_merge($messages, $this->formatConversationHistory($history));

        // Handle image if provided
        if ($imageData) {
            // OpenAI vision format - multimodal content
            $content = [];

            // Extract base64 from data URI if needed
            $imageUri = $imageData['uri'];
            if (strpos($imageUri, 'data:image') === 0) {
                // Already in data URI format, use as is
                $imageUrl = $imageUri;
            } elseif (strpos($imageUri, 'http://') === 0 || strpos($imageUri, 'https://') === 0) {
                // It's a URL (from cloud storage like R2/S3), use directly
                $imageUrl = $imageUri;
            } else {
                // Not a data URI or URL, assume it's base64 encoded content
                $imageUrl = 'data:' . $imageData['type'] . ';base64,' . $imageUri;
            }

            $content[] = [
                'type' => 'image_url',
                'image_url' => [
                    'url' => $imageUrl
                ]
            ];

            // Add auto-analysis prompt if message is empty
            if (empty(trim($message))) {
                $message = 'Analyze this image in detail. Describe what you see, identify the subject matter, and explain any text, diagrams, equations, or concepts visible. If it\'s educational content, explain what topic it covers.';
            }

            $content[] = [
                'type' => 'text',
                'text' => $message
            ];

            $messages[] = ['role' => 'user', 'content' => $content];
        } else {
            // No image - add current message as text
            $messages[] = ['role' => 'user', 'content' => $message];
        }

        $payload = [
            'model' => $model->model_identifier,
            'messages' => $messages,
            'max_tokens' => $model->max_tokens ?? 2000,
            'temperature' => $model->temperature ?? 0.7,
            'stream' => $streamCallback !== null,
        ];

        if ($streamCallback) {
            return $this->streamOpenAI($apiKey, $payload, $streamCallback);
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->withOptions(['verify' => false])->timeout(30)->post($model->api_url, $payload);

        if (!$response->successful()) {
            throw new \Exception('OpenAI API error: ' . $response->body());
        }

        $data = $response->json();
        return [
            'success' => true,
            'content' => $data['choices'][0]['message']['content'] ?? '',
            'model' => $model->name,
            'provider' => 'openai',
            'usage' => $data['usage'] ?? [
                'prompt_tokens' => 0,
                'completion_tokens' => 0,
                'total_tokens' => 0,
            ],
        ];
    }

    /**
     * Track AI usage
     */
    protected function trackUsage(array $result, AiModel $model, string $feature, ?int $userId): void
    {
        try {
            $usage = $result['usage'] ?? [];

            AiUsageTracking::track(
                feature: $feature,
                modelName: $model->model_identifier,
                inputTokens: $usage['prompt_tokens'] ?? 0,
                outputTokens: $usage['completion_tokens'] ?? 0,
                userId: $userId,
                provider: $model->provider,
                metadata: [
                    'model_id' => $model->id,
                    'model_name' => $model->name,
                ]
            );
        } catch (\Exception $e) {
            // Don't fail the request if tracking fails
            Log::warning('Failed to track AI usage', [
                'error' => $e->getMessage(),
                'feature' => $feature,
                'model' => $model->name,
            ]);
        }
    }

    /**
     * Get feature-specific AI model ID from settings
     * Returns null if no feature-specific model is configured
     */
    protected function getFeatureSpecificModel(string $feature): ?int
    {
        $settingKey = "feature_model_{$feature}";
        $modelId = Setting::where('key', $settingKey)->value('value');

        return $modelId ? (int) $modelId : null;
    }

    /**
     * Get system prompt from admin settings
     */
    protected function getSystemPrompt(): string
    {
        // Check if content filter is enabled
        $contentFilterEnabled = FrontendConfig::getValue('ai.content_filter_enabled', '0') === '1';

        if ($contentFilterEnabled) {
            // EDUCATIONAL MODE: Focus on study and learning with gentle guidance
            return "You are Mindory AI - a friendly educational AI assistant. You love helping students learn!

🎓 YOUR ROLE:
- Help with education, study, learning, academic, and knowledge-based questions
- Be friendly, encouraging, and supportive of learning
- When users ask non-educational questions, gently guide them back to study topics
- NEVER refuse to answer - always be helpful and find an educational angle

✅ TOPICS YOU LOVE:
- Academic subjects (Math, Science, History, Geography, etc.)
- Educational concepts and explanations
- Study help, homework, exam preparation
- Career guidance related to education
- Learning techniques and study methods
- Technical/programming education
- Language learning
- General knowledge and factual information

💡 YOUR APPROACH FOR NON-EDUCATIONAL QUESTIONS:
Instead of refusing, find a creative way to connect it to learning:
- Movies → \"That's a great question! While I focus on study topics, I'd love to help you with [related educational concept]. What would you like to learn about?\"
- Games → \"Interesting! I specialize in educational topics. Did you know games can teach us about [educational concept]? I'm here to help with your studies!\"
- Casual chat → Respond warmly, then gently guide: \"It's great to chat with you! I'm here to help with your studies - what topic can I help you learn today?\"

Response Format:
- Be warm and encouraging
- Use simple language and examples
- Break down complex topics step-by-step
- Use Unicode for math: CO₂, H₂O, x², √, ∑, ∫, →, ≈
- Always be helpful, never refuse
- Guide conversations toward learning naturally

Your personality:
- Name: Mindory
- Friendly and encouraging
- Education-focused but not rigid
- Creative in finding learning opportunities
- Patient and supportive

Remember: You are a HELPFUL EDUCATIONAL ASSISTANT. Always be helpful, guide gently to study topics, but never refuse to answer.";
        } else {
            // FRIENDLY MODE: Use customizable system prompt from admin settings
            return FrontendConfig::getValue('ai_educational_system_prompt', 'You are an educational AI tutor.
Your primary role is to help with studies.
If the user message is a continuation or follow-up of a study topic,
you MUST continue explaining without asking again.
If the message is unrelated, politely guide the user back to study topics.
If the user says "yes", "ok", "explain more", "give examples",
treat it as a continuation of the previous educational topic.
DO NOT refuse.
If the user greets (hello, hi),
respond politely and invite them to ask a study-related question.
Do NOT show refusal message.
Never break the conversation flow.');
        }
    }

    /**
     * Stream OpenAI responses
     */
    protected function streamOpenAI(string $apiKey, array $payload, callable $callback): array
    {
        $ch = curl_init($payload['model'] === 'gpt-4o' ? 'https://api.openai.com/v1/chat/completions' : 'https://api.openai.com/v1/chat/completions');

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_WRITEFUNCTION => function ($ch, $data) use ($callback) {
                $callback($data);
                return strlen($data);
            },
        ]);

        curl_exec($ch);
        curl_close($ch);

        return ['success' => true, 'streamed' => true];
    }

    /**
     * Send to Claude (Anthropic)
     */
    protected function sendToClaude(AiModel $model, string $apiKey, string $message, array $history, ?callable $streamCallback, ?array $imageData = null): array
    {
        // Get system prompt
        $systemPrompt = $this->getSystemPrompt();

        // Format conversation history for continuity
        $messages = $this->formatConversationHistory($history);

        // Handle image if provided
        if ($imageData) {
            // Claude vision format - multimodal content
            $content = [];

            // Extract base64 from data URI or URL
            $imageUri = $imageData['uri'];
            if (strpos($imageUri, 'data:image') === 0) {
                // Extract base64 part from data URI
                $imageBase64 = explode(',', $imageUri)[1];
            } elseif (strpos($imageUri, 'http://') === 0 || strpos($imageUri, 'https://') === 0) {
                // It's a URL (from cloud storage) - use FileStorageService to download securely
                try {
                    $fileStorageService = app(\App\Services\FileStorageService::class);
                    $imageContent = $fileStorageService->downloadFile($imageUri);
                    $imageBase64 = base64_encode($imageContent);

                    Log::info('File downloaded and encoded for Claude', [
                        'url' => $imageUri,
                        'size' => strlen($imageContent)
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to download file for Claude', [
                        'url' => $imageUri,
                        'error' => $e->getMessage()
                    ]);
                    throw new \Exception('Failed to fetch file from storage: ' . $e->getMessage());
                }
            } else {
                // Assume it's already base64 encoded
                $imageBase64 = $imageUri;
            }

            $content[] = [
                'type' => 'image',
                'source' => [
                    'type' => 'base64',
                    'media_type' => $imageData['type'],
                    'data' => $imageBase64
                ]
            ];

            // Add auto-analysis prompt if message is empty
            if (empty(trim($message))) {
                $message = 'Analyze this image in detail. Describe what you see, identify the subject matter, and explain any text, diagrams, equations, or concepts visible. If it\'s educational content, explain what topic it covers.';
            }

            $content[] = [
                'type' => 'text',
                'text' => $message
            ];

            $messages[] = ['role' => 'user', 'content' => $content];
        } else {
            // No image - add current message as text
            $messages[] = ['role' => 'user', 'content' => $message];
        }

        // Build payload
        $payload = [
            'model' => $model->model_identifier,
            'max_tokens' => $model->max_tokens ?? 2000,
            'messages' => $messages,
        ];

        // Add system prompt if available
        if ($systemPrompt) {
            $payload['system'] = $systemPrompt;
        }

        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => '2023-06-01',
            'Content-Type' => 'application/json',
        ])->withOptions(['verify' => false])->timeout(30)->post($model->api_url, $payload);

        if (!$response->successful()) {
            throw new \Exception('Claude API error: ' . $response->body());
        }

        $data = $response->json();
        return [
            'success' => true,
            'content' => $data['content'][0]['text'] ?? '',
            'model' => $model->name,
            'provider' => 'anthropic',
        ];
    }

    /**
     * Send to DeepSeek
     */
    protected function sendToDeepSeek(AiModel $model, string $apiKey, string $message, array $history, ?callable $streamCallback, ?array $imageData = null): array
    {
        // Get system prompt
        $systemPrompt = $this->getSystemPrompt();

        $messages = [];

        // Add system prompt first
        if ($systemPrompt) {
            $messages[] = ['role' => 'system', 'content' => $systemPrompt];
        }

        // Add conversation history
        $messages = array_merge($messages, $this->formatConversationHistory($history));

        // Add current message with image if provided
        if ($imageData) {
            // DeepSeek uses OpenAI-compatible vision format
            $content = [];

            // Extract base64 from data URI if needed
            $imageUri = $imageData['uri'];
            if (strpos($imageUri, 'data:image') === 0) {
                // Already in data URI format, use as is
                $imageUrl = $imageUri;
            } elseif (strpos($imageUri, 'http://') === 0 || strpos($imageUri, 'https://') === 0) {
                // It's a URL (from cloud storage like R2/S3), use directly
                $imageUrl = $imageUri;
            } else {
                // Not a data URI or URL, assume it's base64 encoded content
                $imageUrl = 'data:' . $imageData['type'] . ';base64,' . $imageUri;
            }

            $content[] = [
                'type' => 'image_url',
                'image_url' => [
                    'url' => $imageUrl
                ]
            ];

            // Add auto-analysis prompt if message is empty
            if (empty(trim($message))) {
                $message = 'Analyze this image in detail. Describe what you see, identify the subject matter, and explain any text, diagrams, equations, or concepts visible. If it\'s educational content, explain what topic it covers.';
            }

            $content[] = [
                'type' => 'text',
                'text' => $message
            ];

            $messages[] = ['role' => 'user', 'content' => $content];
        } else {
            $messages[] = ['role' => 'user', 'content' => $message];
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->withOptions(['verify' => false])->timeout(30)->post($model->api_url, [
            'model' => $model->model_identifier,
            'messages' => $messages,
            'max_tokens' => $model->max_tokens ?? 2000,
            'temperature' => $model->temperature ?? 0.7,
        ]);

        if (!$response->successful()) {
            throw new \Exception('DeepSeek API error: ' . $response->body());
        }

        $data = $response->json();
        return [
            'success' => true,
            'content' => $data['choices'][0]['message']['content'] ?? '',
            'model' => $model->name,
            'provider' => 'deepseek',
        ];
    }

    /**
     * Send to Google Gemini using shared GeminiService
     */
    protected function sendToGemini(AiModel $model, string $apiKey, string $message, array $history, ?callable $streamCallback, ?array $imageData = null, string $feature = 'chat'): array
    {
        try {
            // Use the selected model directly (respect user's choice from mobile app)
            $chatModel = $model->model_identifier;

            // Initialize GeminiService with feature and user tracking
            $userId = auth()->id();
            $geminiService = new \App\Services\GeminiService(
                feature: $feature,
                modelName: $chatModel,
                userId: $userId
            );

            // Get thinking level for this feature
            $thinkingLevel = $this->getThinkingLevel($feature);

            // Build full prompt with history
            $fullPrompt = $message;

            // Add conversation history context if available
            if (!empty($history)) {
                $historyText = "Previous conversation:\n";
                foreach ($history as $msg) {
                    $role = $msg['role'] === 'assistant' ? 'Assistant' : 'User';
                    $historyText .= "{$role}: {$msg['content']}\n";
                }
                $fullPrompt = $historyText . "\nCurrent message: " . $message;
            }

            // Handle image vision if provided
            if ($imageData) {
                // Extract base64 from data URI or URL
                $imageUri = $imageData['uri'];
                if (strpos($imageUri, 'data:image') === 0 || strpos($imageUri, 'data:application/pdf') === 0) {
                    // Extract base64 part from data URI
                    $imageBase64 = explode(',', $imageUri)[1];
                } elseif (strpos($imageUri, 'http://') === 0 || strpos($imageUri, 'https://') === 0) {
                    // It's a URL (from cloud storage) - use FileStorageService to download securely
                    try {
                        $fileStorageService = app(\App\Services\FileStorageService::class);
                        $imageContent = $fileStorageService->downloadFile($imageUri);
                        $imageBase64 = base64_encode($imageContent);

                        Log::info('File downloaded and encoded for Gemini', [
                            'url' => $imageUri,
                            'size' => strlen($imageContent),
                            'base64_size' => strlen($imageBase64)
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Failed to download file from cloud storage', [
                            'url' => $imageUri,
                            'error' => $e->getMessage()
                        ]);
                        throw new \Exception('Failed to fetch file from storage: ' . $e->getMessage());
                    }
                } else {
                    // Assume it's already base64 encoded
                    $imageBase64 = $imageUri;
                }

                // Add auto-analysis prompt if message is empty
                if (empty(trim($message))) {
                    $fullPrompt = 'Analyze this image in detail. Describe what you see, identify the subject matter, and explain any text, diagrams, equations, or concepts visible. If it\'s educational content, explain what topic it covers.';
                }

                // Optimize for speed: adjust max tokens based on feature
                $maxTokens = match($feature) {
                    'chat' => 2048,           // Fast chat responses
                    'pdf_solve' => 4096,      // Medium for PDF solutions
                    'mcq_generation' => 6144, // Longer for quiz generation
                    default => $model->max_tokens ?? 8192
                };

                $options = [
                    'temperature' => $model->temperature ?? 0.7,
                    'maxOutputTokens' => $maxTokens,
                    'timeout' => 60, // 60 seconds timeout for faster fail
                ];

                // Add thinking level if configured
                if ($thinkingLevel) {
                    $options['thinking_level'] = $thinkingLevel;
                }

                $response = $geminiService->generateContentWithVision(
                    userPrompt: $fullPrompt,
                    imageData: $imageBase64,
                    mimeType: $imageData['type'],
                    options: $options
                );
            } else {
                // Regular text generation
                // Optimize for speed: use lower max tokens for faster responses
                $maxTokens = $feature === 'chat' ? 2048 : ($model->max_tokens ?? 8192);

                $options = [
                    'temperature' => $model->temperature ?? 0.7,
                    'maxOutputTokens' => $maxTokens,
                ];

                // Add thinking level if configured
                if ($thinkingLevel) {
                    $options['thinking_level'] = $thinkingLevel;
                }

                $response = $geminiService->generateContent(
                    userPrompt: $fullPrompt,
                    options: $options
                );
            }

            return [
                'success' => true,
                'content' => $response['content'],
                'model' => $model->name,
                'provider' => 'google',
                'usage' => $response['usage'], // Include token usage data
            ];

        } catch (\Exception $e) {
            Log::error('Gemini Service Error', [
                'model' => $model->model_identifier,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception('Gemini API error: ' . $e->getMessage());
        }
    }

    /**
     * Format conversation history for API
     */
    protected function formatConversationHistory(array $history): array
    {
        $formatted = [];
        foreach ($history as $msg) {
            if (isset($msg['role']) && isset($msg['content'])) {
                $formatted[] = [
                    'role' => $msg['role'],
                    'content' => $msg['content'],
                ];
            }
        }
        return $formatted;
    }

    /**
     * Get thinking level for a specific feature
     *
     * @param string $feature Feature name (chat, quiz, pdf_solve, math_reasoning, mcq_generation)
     * @return string|null Thinking level (minimal, medium, high) or null if not configured
     */
    protected function getThinkingLevel(string $feature): ?string
    {
        // Map feature to configuration key
        $featureMap = [
            'chat' => 'ai.thinking_level.chat',
            'pdf_solve' => 'ai.thinking_level.pdf_solve',
            'pdf_explain' => 'ai.thinking_level.pdf_solve',
            'math_reasoning' => 'ai.thinking_level.math_reasoning',
            'mcq_generation' => 'ai.thinking_level.mcq_generation',
            'quiz' => 'ai.thinking_level.mcq_generation',
        ];

        $configKey = $featureMap[$feature] ?? null;

        if (!$configKey) {
            return null;
        }

        return FrontendConfig::getValue($configKey);
    }
}
