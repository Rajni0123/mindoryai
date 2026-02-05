<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\AiModel;
use App\Models\FrontendConfig;
use App\Services\UsageTrackingService;
use App\Services\UsageLimitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * ConversationController
 *
 * Handles ChatGPT-style conversation threads
 * - Creating new conversations
 * - Sending messages within conversations
 * - Maintaining conversation history
 * - Integration with OpenAI API
 */
class ConversationController extends Controller
{
    protected UsageLimitService $usageLimitService;

    public function __construct(UsageLimitService $usageLimitService)
    {
        $this->usageLimitService = $usageLimitService;
    }

    /**
     * Get or create active conversation for current user
     *
     * FLOW:
     * 1. Check session for active conversation_id
     * 2. If found, load it
     * 3. If not found, create new conversation
     * 4. Store conversation_id in session
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOrCreateConversation(Request $request)
    {
        $user = Auth::user();

        // Get conversation_id from session
        $conversationId = session('conversation_id');

        // Try to load existing conversation
        if ($conversationId) {
            $conversation = Conversation::where('id', $conversationId)
                                       ->where('user_id', $user->id)
                                       ->first();

            if ($conversation) {
                return response()->json([
                    'success' => true,
                    'conversation' => $conversation->load('messages'),
                ]);
            }
        }

        // Create new conversation if none exists
        $aiModel = AiModel::where('is_active', true)->orderBy('order')->first();

        $conversation = Conversation::create([
            'user_id' => $user->id,
            'title' => 'New Chat',
            'ai_model_id' => $aiModel->id ?? null,
            'last_message_at' => now(),
        ]);

        // Store in session
        session(['conversation_id' => $conversation->id]);

        return response()->json([
            'success' => true,
            'conversation' => $conversation,
        ]);
    }

    /**
     * Send a message and get AI response
     *
     * CRITICAL FLOW (ChatGPT-style):
     * 1. Get or create conversation
     * 2. Save user message to database
     * 3. Get ALL messages from this conversation
     * 4. Send full message history to OpenAI
     * 5. Stream AI response back
     * 6. Save AI response to database
     *
     * This ensures AI remembers previous messages!
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function sendMessage(Request $request)
    {
        $user = Auth::user();

        // Check usage limits BEFORE processing
        if ($user && $user->role !== 'admin') {
            $limitCheck = $this->usageLimitService->canUse($user, 'chat');
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

        $request->validate([
            'message' => 'required|string|min:1|max:5000',
            'model_id' => 'nullable|integer|exists:ai_models,id',
        ]);

        $userMessage = $request->input('message');
        $modelId = $request->input('model_id');

        // STEP 1: Get or create conversation
        $conversationId = session('conversation_id');
        $conversation = null;

        if ($conversationId) {
            $conversation = Conversation::where('id', $conversationId)
                                       ->where('user_id', $user->id)
                                       ->first();
        }

        // Create new conversation if none exists
        if (!$conversation) {
            $aiModel = $modelId
                ? AiModel::find($modelId)
                : AiModel::where('is_active', true)->orderBy('order')->first();

            $conversation = Conversation::create([
                'user_id' => $user->id,
                'title' => Conversation::generateTitle($userMessage),
                'ai_model_id' => $aiModel->id ?? null,
                'last_message_at' => now(),
            ]);

            session(['conversation_id' => $conversation->id]);
        }

        // STEP 2: Save user message to database
        $userMessageModel = Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $userMessage,
            'tokens' => Message::estimateTokens($userMessage),
        ]);

        // STEP 3: Get ALL messages from this conversation for context
        // This is CRITICAL for AI to remember previous messages
        $messages = $conversation->getMessagesForAPI();

        // STEP 4: Get AI model configuration
        $aiModel = $conversation->aiModel ?? AiModel::where('is_active', true)->orderBy('order')->first();

        if (!$aiModel) {
            return response()->json(['error' => 'No active AI model found. Please contact administrator.'], 400);
        }

        // Check if provider is enabled in admin settings
        if (!$this->isProviderEnabled($aiModel->provider)) {
            return response()->json(['error' => ucfirst($aiModel->provider) . ' is not enabled. Please enable it in AI Settings.'], 400);
        }

        // Get API key from admin panel
        $apiKey = $this->getApiKeyForProvider($aiModel->provider);

        if (!$apiKey) {
            return response()->json(['error' => ucfirst($aiModel->provider) . ' API key not configured in admin panel.'], 400);
        }

        // Get user's plan for system prompt
        $user->load('userPlan');
        $userPlan = $user->userPlan;

        // Build system prompt based on plan
        $systemPrompt = $this->buildSystemPrompt($userPlan, $user->student_class);

        // Add system message at the beginning
        array_unshift($messages, ['role' => 'system', 'content' => $systemPrompt]);

        // STEP 5: Stream response from OpenAI with FULL conversation history
        return response()->stream(function () use ($messages, $conversation, $aiModel, $apiKey, $user, $userMessage) {
            $fullResponse = '';

            // Build headers
            $headers = ['Content-Type: application/json'];
            if ($aiModel->provider === 'anthropic') {
                $headers[] = 'x-api-key: ' . $apiKey;
                $headers[] = 'anthropic-version: 2023-06-01';
            } else {
                $headers[] = 'Authorization: Bearer ' . $apiKey;
            }

            // Build payload
            $payload = [
                'model' => $aiModel->model_identifier,
                'temperature' => 0.5,
                'stream' => true,
                'messages' => $messages, // FULL conversation history sent here
            ];

            if ($aiModel->provider === 'anthropic') {
                $payload['max_tokens'] = (int) ($aiModel->max_tokens ?? 3000);
            } else {
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

                            // Handle different provider formats
                            if ($aiModel->provider === 'anthropic') {
                                if (isset($decoded['delta']['text'])) {
                                    $content = $decoded['delta']['text'];
                                } elseif (isset($decoded['content'][0]['text'])) {
                                    $content = $decoded['content'][0]['text'];
                                }
                            } else {
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
                    'conversation_id' => $conversation->id,
                    'error' => $curlError
                ]);
                echo "data: " . json_encode(['error' => 'Connection error: ' . $curlError]) . "\n\n";
            } elseif ($httpCode !== 200) {
                Log::error('AI API error', [
                    'conversation_id' => $conversation->id,
                    'http_code' => $httpCode
                ]);
                echo "data: " . json_encode(['error' => 'AI API error (HTTP ' . $httpCode . ')']) . "\n\n";
            } elseif (empty($fullResponse)) {
                echo "data: " . json_encode(['error' => 'No response received from AI']) . "\n\n";
            } else {
                // STEP 6: Save AI response to database
                Message::create([
                    'conversation_id' => $conversation->id,
                    'role' => 'assistant',
                    'content' => $fullResponse,
                    'tokens' => Message::estimateTokens($fullResponse),
                ]);

                // Track usage
                $estimatedTokens = (int) ((strlen($userMessage) + strlen($fullResponse)) / 4);
                UsageTrackingService::trackMessage($user->id, $aiModel->model_identifier, $estimatedTokens);
                UsageTrackingService::trackApiCall($user->id, $aiModel->model_identifier, $estimatedTokens, ['type' => 'chat_stream', 'conversation_id' => $conversation->id], null);
                // Record usage for daily limits
                $this->usageLimitService->recordUsage($user, 'chat');

                Log::info('Message added to conversation', [
                    'conversation_id' => $conversation->id,
                    'messages_count' => $conversation->messages()->count(),
                ]);
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
    }

    /**
     * Create a new conversation (New Chat button)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createNewConversation(Request $request)
    {
        $user = Auth::user();
        $aiModel = AiModel::where('is_active', true)->orderBy('order')->first();

        $conversation = Conversation::create([
            'user_id' => $user->id,
            'title' => 'New Chat',
            'ai_model_id' => $aiModel->id ?? null,
            'last_message_at' => now(),
        ]);

        // Update session with new conversation ID
        session(['conversation_id' => $conversation->id]);

        return response()->json([
            'success' => true,
            'conversation' => $conversation,
        ]);
    }

    /**
     * Get all conversations for logged-in user
     * For sidebar conversation list
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserConversations()
    {
        $user = Auth::user();

        $conversations = Conversation::forUser($user->id)
                                    ->with('messages')
                                    ->get();

        return response()->json([
            'success' => true,
            'conversations' => $conversations,
        ]);
    }

    /**
     * Load a specific conversation
     * When user clicks on a conversation in sidebar
     *
     * @param Conversation $conversation
     * @return \Illuminate\Http\JsonResponse
     */
    public function loadConversation(Conversation $conversation)
    {
        $user = Auth::user();

        // Ensure user owns this conversation
        if ($conversation->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Set as active conversation in session
        session(['conversation_id' => $conversation->id]);

        return response()->json([
            'success' => true,
            'conversation' => $conversation->load('messages'),
        ]);
    }

    /**
     * Delete a conversation
     *
     * @param Conversation $conversation
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteConversation(Conversation $conversation)
    {
        $user = Auth::user();

        // Ensure user owns this conversation
        if ($conversation->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // If this is the active conversation, clear session
        if (session('conversation_id') == $conversation->id) {
            session()->forget('conversation_id');
        }

        $conversation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Conversation deleted successfully',
        ]);
    }

    /**
     * Build system prompt based on user plan
     *
     * @param mixed $userPlan
     * @param string|null $classLevel
     * @return string
     */
    private function buildSystemPrompt($userPlan, $classLevel = null): string
    {
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

        $systemPrompt = "You are an AI Study Assistant designed ONLY for educational purposes.\n";
        $systemPrompt .= "This user is subscribed to the {$planName} ({$planPrice}).\n\n";

        $systemPrompt .= "PLAN LIMITS:\n";
        $systemPrompt .= "- Monthly token limit: {$monthlyTokenLimit} tokens\n";
        $systemPrompt .= "- Daily request limit: Maximum {$dailyRequestLimit} requests per day\n";
        $systemPrompt .= "- Average token usage per response: {$avgTokenUsage} tokens\n";
        $systemPrompt .= "- Maximum tokens per response: {$maxTokensPerResponse} tokens\n\n";

        $systemPrompt .= "CORE RESPONSIBILITIES:\n";
        $systemPrompt .= "- Provide academic support for Indian school students (Classes 1-10, CBSE/NCERT)\n";
        $systemPrompt .= "- Optimize responses to save tokens while maintaining clarity\n";

        if ($isPro) {
            $systemPrompt .= "- Provide detailed but efficient educational explanations\n";
        } else {
            $systemPrompt .= "- Always be concise and to the point\n";
        }

        $systemPrompt .= "\nSTRICT OUTPUT RULES:\n";
        $systemPrompt .= "1. Write in simple plain text\n";
        $systemPrompt .= "2. Use clear steps like: Step 1, Step 2, Step 3\n";
        $systemPrompt .= "3. Keep responses SHORT and CONCISE to save tokens\n";
        $systemPrompt .= "4. Always write the final result clearly\n\n";

        if ($classLevel) {
            $systemPrompt .= "The student is in {$classLevel}. Adjust your language to their level.\n";
        }

        $systemPrompt .= "\nYour purpose is STRICTLY educational assistance with controlled usage.";

        return $systemPrompt;
    }

    /**
     * Get API key for provider
     *
     * @param string $provider
     * @return string|null
     */
    /**
     * Check if provider is enabled in admin settings
     * STRICTLY from admin panel ONLY - no .env fallback
     */
    private function isProviderEnabled(string $provider): bool
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

        // STRICTLY check admin panel only - default to disabled if not set
        $enabled = FrontendConfig::getValue($enabledKey, '0');
        return $enabled === '1';
    }

    /**
     * Get API key for provider
     * STRICTLY from admin panel ONLY - NO .env fallback
     */
    private function getApiKeyForProvider(string $provider): ?string
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

        // STRICTLY from admin panel (FrontendConfig) ONLY - NO .env fallback
        $apiKey = FrontendConfig::getValue($key, '');

        return $apiKey ?: null;
    }
}
