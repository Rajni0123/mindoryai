<?php

namespace App\Services;

use App\Models\AiModel;
use App\Models\FrontendConfig;
use App\Models\AiUsageTracking;
use App\Models\Setting;
use App\Models\User;
use App\Services\AIPersonalityService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UnifiedAIService
{
    /**
     * Get user's plan-based speed settings
     * Premium users get priority processing and optimized settings
     */
    /**
     * Cache for plan speed settings to avoid repeated DB queries
     */
    protected static array $speedSettingsCache = [];

    /**
     * Cache for system prompts to avoid repeated DB queries
     */
    protected static ?string $systemPromptCache = null;

    /**
     * Cache for provider enabled status
     */
    protected static array $providerEnabledCache = [];

    /**
     * Current language preference for responses
     */
    protected string $currentLanguage = 'english';

    /**
     * Active feature for the current request (chat, ai_doubt, quiz, etc.)
     */
    protected string $currentFeature = 'chat';

    /**
     * Cache for user plan slugs to avoid repeated DB queries
     */
    protected static array $userPlanSlugCache = [];

    /**
     * Optional retrieval context injected by hybrid engine for current request.
     */
    protected string $retrievalContext = '';

    protected array $retrievalMeta = [];

    // =========================================================================
    // FAILOVER & ADAPTIVE MODEL SELECTION SYSTEM
    // =========================================================================

    /**
     * Get user's plan slug with caching
     * Returns: 'lite', 'pro', 'ultimate', or empty string when no active subscription
     *
     * @param int|null $userId User ID
     * @return string Plan slug
     */
    protected function getUserPlanSlug(?int $userId): string
    {
        if (!$userId) {
            return '';
        }

        // Check cache first
        if (isset(self::$userPlanSlugCache[$userId])) {
            return self::$userPlanSlugCache[$userId];
        }

        try {
            $user = User::find($userId);
            if (!$user || !$user->plan_id) {
                self::$userPlanSlugCache[$userId] = '';
                return '';
            }

            $plan = $user->userPlan;
            $slug = $plan ? ($plan->slug ?? '') : '';

            self::$userPlanSlugCache[$userId] = $slug;
            return $slug;
        } catch (\Exception $e) {
            Log::warning('Failed to get user plan slug', ['user_id' => $userId, 'error' => $e->getMessage()]);
            return '';
        }
    }

    /**
     * Detect if a question is complex and needs a better model
     *
     * Complexity indicators:
     * - Question length > 150 characters
     * - Contains math symbols (∫, ∑, √, ², ³, ≠, ≤, ≥, ∞, π, θ, α, β, δ, Δ)
     * - Contains equations (=, +, -, *, /)
     * - Feature type is scan_solve, pdf_solve, mcq_generation, or quiz
     *
     * @param string $question The user's question
     * @param string $feature The feature being used
     * @return bool True if question is complex
     */
    protected function isQuestionComplex(string $question, string $feature): bool
    {
        // Feature-based complexity: these always need best model
        $complexFeatures = ['scan_solve', 'pdf_solve', 'mcq_generation', 'quiz', 'math_reasoning'];
        if (in_array($feature, $complexFeatures)) {
            return true;
        }

        // Length-based complexity: long questions are usually complex
        if (mb_strlen($question) > 150) {
            return true;
        }

        // Math symbols indicate complex mathematical content
        $mathSymbols = ['∫', '∑', '√', '²', '³', '≠', '≤', '≥', '∞', 'π', 'θ', 'α', 'β', 'δ', 'Δ', 'λ', 'σ', 'μ'];
        foreach ($mathSymbols as $symbol) {
            if (mb_strpos($question, $symbol) !== false) {
                return true;
            }
        }

        // Mathematical equation patterns
        // Check for equation-like patterns (numbers with operators)
        if (preg_match('/\d+\s*[\+\-\*\/\=]\s*\d+/', $question)) {
            return true;
        }

        // Physics/Chemistry formulas (e.g., H2O, CO2, E=mc²)
        if (preg_match('/[A-Z][a-z]?\d+|[A-Z]\s*=\s*[a-z]/i', $question)) {
            return true;
        }

        // Keywords that indicate complex questions
        $complexKeywords = [
            'derive', 'proof', 'prove', 'integration', 'differentiation', 'calculus',
            'thermodynamics', 'electromagnetic', 'quantum', 'organic chemistry',
            'mechanism', 'synthesis', 'numericals', 'solve step by step',
            'explain in detail', 'derivation'
        ];
        $questionLower = mb_strtolower($question);
        foreach ($complexKeywords as $keyword) {
            if (mb_strpos($questionLower, $keyword) !== false) {
                return true;
            }
        }

        // JEE / NEET / CBSE exam keywords
        $examKeywords = [
            'jee', 'neet', 'numerical', 'ncert', 'cbse', 'iit', 'mock test',
            'previous year', 'pyq', 'organic', 'inorganic', 'coordinate geometry',
            'trigonometry', 'probability', 'vectors', 'electrostatics', 'optics',
        ];
        foreach ($examKeywords as $keyword) {
            if (mb_strpos($questionLower, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Select the appropriate AI model based on question complexity and user's plan
     *
     * Model Selection Logic:
     * - Free plan: Always use basic model (no adaptive)
     * - Lite plan: Always use basic model (no adaptive, but has failover)
     * - Pro/Ultimate: Adaptive switching based on complexity
     *   - Simple questions → cheap/fast model (gpt-4o-mini or gemini-2.0-flash-lite)
     *   - Complex questions → best model (gpt-4o or gemini-1.5-pro)
     *
     * @param string $question The user's question
     * @param string $feature The feature being used
     * @param string $planSlug User's plan slug
     * @param string $preferredProvider Preferred AI provider ('google', 'openai')
     * @return array ['model_identifier' => string, 'provider' => string, 'tier' => string]
     */
    protected function resolvePrimaryProvider(string $feature): string
    {
        $openAiFirst = config('ai.openai_first_features', ['chat', 'ai_doubt']);

        if (in_array($feature, $openAiFirst, true)) {
            $preferred = config('ai.chat_primary_provider', 'openai');
            if ($preferred === 'openai' && $this->isProviderEnabled('openai')) {
                return 'openai';
            }
            if ($preferred === 'google' && $this->isProviderEnabled('google')) {
                return 'google';
            }
        }

        if ($this->isProviderEnabled('openai')) {
            return 'openai';
        }

        return $this->isProviderEnabled('google') ? 'google' : 'openai';
    }

    protected function isChatFeature(string $feature): bool
    {
        return in_array($feature, ['chat', 'ai_doubt'], true);
    }

    protected function isVisionFeature(string $feature): bool
    {
        return in_array($feature, ['scan_solve', 'pdf_solve'], true);
    }

    protected function isQuizFeature(string $feature): bool
    {
        return in_array($feature, ['quiz', 'mcq_generation', 'exam_prep'], true);
    }

    protected function selectModel(string $question, string $feature, string $planSlug, string $preferredProvider = 'openai'): array
    {
        // ── OpenAI feature-specific routing (BlinkStudy optimal stack) ──
        if ($preferredProvider === 'openai' && $this->isProviderEnabled('openai')) {
            if ($this->isVisionFeature($feature)) {
                return [
                    'model_identifier' => config('ai.vision_model', 'gpt-4o'),
                    'provider' => 'openai',
                    'tier' => 'best',
                ];
            }

            if ($this->isQuizFeature($feature)) {
                return [
                    'model_identifier' => config('ai.quiz_model', 'gpt-4o-mini'),
                    'provider' => 'openai',
                    'tier' => 'fast',
                ];
            }

            if ($this->isChatFeature($feature)) {
                $isComplex = config('ai.adaptive_models', true)
                    && $this->isQuestionComplex($question, $feature);

                return [
                    'model_identifier' => $isComplex
                        ? config('ai.chat_complex_model', 'gpt-4o')
                        : config('ai.chat_model', 'gpt-4o-mini'),
                    'provider' => 'openai',
                    'tier' => $isComplex ? 'best' : 'fast',
                ];
            }
        }

        // ── Gemini / fallback tiers ──
        $modelTiers = [
            'google' => [
                'basic' => 'gemini-2.0-flash',
                'fast' => 'gemini-2.0-flash-lite',
                'best' => 'gemini-2.0-flash',
            ],
            'openai' => [
                'basic' => config('ai.chat_model', 'gpt-4o-mini'),
                'fast' => config('ai.chat_model', 'gpt-4o-mini'),
                'best' => config('ai.chat_complex_model', 'gpt-4o'),
            ],
        ];

        $useAdaptive = config('ai.adaptive_models', true);

        if ($useAdaptive) {
            $isComplex = $this->isQuestionComplex($question, $feature);
            $tier = $isComplex ? 'best' : 'fast';
            $modelId = $modelTiers[$preferredProvider][$tier] ?? $modelTiers['google']['basic'];

            return [
                'model_identifier' => $modelId,
                'provider' => $preferredProvider,
                'tier' => $tier,
            ];
        }

        $tier = 'basic';
        $modelId = $modelTiers[$preferredProvider][$tier] ?? $modelTiers['google']['basic'];

        return [
            'model_identifier' => $modelId,
            'provider' => $preferredProvider,
            'tier' => $tier,
        ];
    }

    /**
     * Execute AI call with automatic failover to backup provider
     *
     * Failover Logic:
     * - Free plan: NO failover (single provider only)
     * - Lite/Pro/Ultimate: Failover enabled
     *   - Primary: Gemini (Google)
     *   - Backup: OpenAI (GPT)
     *   - If both fail: Return error
     *
     * @param string $message User message
     * @param array $conversationHistory Previous messages
     * @param callable|null $streamCallback Streaming callback
     * @param array|null $imageData Image data if present
     * @param string $feature Feature name
     * @param int|null $userId User ID
     * @param array $speedSettings Plan-based speed settings
     * @param string $planSlug User's plan slug
     * @return array Response with content and metadata
     */
    protected function callWithFailover(
        string $message,
        array $conversationHistory,
        ?callable $streamCallback,
        ?array $imageData,
        string $feature,
        ?int $userId,
        array $speedSettings,
        string $planSlug
    ): array {
        // Failover when both providers are configured (all plans)
        $failoverEnabled = $this->isProviderEnabled('openai') && $this->isProviderEnabled('google');

        // Select primary model — OpenAI/GPT first for chat when configured
        $preferredProvider = $this->resolvePrimaryProvider($feature);
        $primarySelection = $this->selectModel($message, $feature, $planSlug, $preferredProvider);
        $primaryProvider = $primarySelection['provider'];
        $primaryModelId = $primarySelection['model_identifier'];

        // Get primary model from database
        $primaryModel = AiModel::where('model_identifier', $primaryModelId)
            ->where('is_active', true)
            ->first();

        // Fallback to any active model if specific model not found
        if (!$primaryModel) {
            $primaryModel = AiModel::where('provider', $primaryProvider)
                ->where('is_active', true)
                ->orderBy('order')
                ->first();
        }

        // Try primary provider
        if ($primaryModel && $this->isProviderEnabled($primaryModel->provider)) {
            $apiKey = $this->getApiKey($primaryModel->provider);
            if ($apiKey) {
                try {
                    Log::info('AI Request - Primary provider', [
                        'provider' => $primaryModel->provider,
                        'model' => $primaryModel->model_identifier,
                        'user_id' => $userId,
                        'plan' => $planSlug,
                        'failover_enabled' => $failoverEnabled,
                    ]);

                    $result = $this->executeProviderCall(
                        $primaryModel,
                        $apiKey,
                        $message,
                        $conversationHistory,
                        $streamCallback,
                        $imageData,
                        $feature,
                        $speedSettings,
                        $userId
                    );

                    if ($result['success']) {
                        $result['used_failover'] = false;
                        $result['primary_provider'] = $primaryModel->provider;
                        return $result;
                    }

                    Log::warning('Primary provider failed', [
                        'provider' => $primaryModel->provider,
                        'error' => $result['error'] ?? 'Unknown error',
                    ]);

                } catch (\Exception $e) {
                    Log::error('Primary provider exception', [
                        'provider' => $primaryModel->provider,
                        'error' => $e->getMessage(),
                    ]);

                    // If failover is disabled, return error immediately
                    if (!$failoverEnabled) {
                        return [
                            'success' => false,
                            'error' => 'AI service temporarily unavailable. Please try again.',
                            'used_failover' => false,
                        ];
                    }
                }
            }
        }

        // If failover is disabled, return error
        if (!$failoverEnabled) {
            return [
                'success' => false,
                'error' => 'AI service not available. Please try again later.',
                'used_failover' => false,
            ];
        }

        // FAILOVER: Try backup provider (OpenAI ↔ Gemini)
        $backupProvider = $primaryProvider === 'openai' ? 'google' : 'openai';

        Log::info('Initiating failover to backup provider', [
            'primary_provider' => $primaryProvider,
            'backup_provider' => $backupProvider,
            'user_id' => $userId,
        ]);

        // Select backup model (maintaining complexity tier)
        $backupSelection = $this->selectModel($message, $feature, $planSlug, $backupProvider);
        $backupModelId = $backupSelection['model_identifier'];

        $backupModel = AiModel::where('model_identifier', $backupModelId)
            ->where('is_active', true)
            ->first();

        // Fallback to any active model from backup provider
        if (!$backupModel) {
            $backupModel = AiModel::where('provider', $backupProvider)
                ->where('is_active', true)
                ->orderBy('order')
                ->first();
        }

        if ($backupModel && $this->isProviderEnabled($backupModel->provider)) {
            $apiKey = $this->getApiKey($backupModel->provider);
            if ($apiKey) {
                try {
                    Log::info('AI Request - Failover provider', [
                        'provider' => $backupModel->provider,
                        'model' => $backupModel->model_identifier,
                        'user_id' => $userId,
                    ]);

                    $result = $this->executeProviderCall(
                        $backupModel,
                        $apiKey,
                        $message,
                        $conversationHistory,
                        $streamCallback,
                        $imageData,
                        $feature,
                        $speedSettings,
                        $userId
                    );

                    if ($result['success']) {
                        $result['used_failover'] = true;
                        $result['primary_provider'] = $primaryProvider;
                        $result['failover_provider'] = $backupModel->provider;

                        Log::info('Failover successful', [
                            'primary_provider' => $primaryProvider,
                            'failover_provider' => $backupModel->provider,
                        ]);

                        return $result;
                    }

                } catch (\Exception $e) {
                    Log::error('Failover provider exception', [
                        'provider' => $backupModel->provider,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        // Both providers failed
        Log::error('All AI providers failed', [
            'primary_provider' => $primaryProvider,
            'backup_provider' => 'openai',
            'user_id' => $userId,
        ]);

        return [
            'success' => false,
            'error' => 'All AI services are currently unavailable. Please try again in a few moments.',
            'used_failover' => true,
        ];
    }

    /**
     * Execute a call to a specific AI provider
     * Helper method to route to the appropriate provider method
     *
     * @param AiModel $model The AI model to use
     * @param string $apiKey API key for the provider
     * @param string $message User message
     * @param array $history Conversation history
     * @param callable|null $streamCallback Streaming callback
     * @param array|null $imageData Image data
     * @param string $feature Feature name
     * @param array $speedSettings Speed settings
     * @param int|null $userId User ID
     * @return array Response array
     */
    protected function executeProviderCall(
        AiModel $model,
        string $apiKey,
        string $message,
        array $history,
        ?callable $streamCallback,
        ?array $imageData,
        string $feature,
        array $speedSettings,
        ?int $userId
    ): array {
        return match ($model->provider) {
            'openai' => $this->sendToOpenAI($model, $apiKey, $message, $history, $streamCallback, $imageData, $speedSettings, $userId),
            'anthropic' => $this->sendToClaude($model, $apiKey, $message, $history, $streamCallback, $imageData, $speedSettings, $userId),
            'deepseek' => $this->sendToDeepSeek($model, $apiKey, $message, $history, $streamCallback, $imageData, $speedSettings, $userId),
            'google' => $this->sendToGemini($model, $apiKey, $message, $history, $streamCallback, $imageData, $feature, $speedSettings, $userId),
            default => [
                'success' => false,
                'error' => 'Unsupported AI provider: ' . $model->provider,
            ],
        };
    }

    // =========================================================================
    // END FAILOVER & ADAPTIVE MODEL SELECTION SYSTEM
    // =========================================================================

    protected function getPlanSpeedSettings(?int $userId): array
    {
        // FREE plan defaults - standard speed (paid users get priority)
        $defaults = [
            'max_tokens' => 1536,       // Standard tokens for free
            'timeout' => 20,            // Standard timeout
            'connect_timeout' => 6,     // Standard connection
            'priority' => 'low',        // Lower priority than paid users
            'use_best_model' => false,  // Use basic model only
        ];

        if (!$userId) {
            return $defaults;
        }

        // Check cache first for faster response
        if (isset(self::$speedSettingsCache[$userId])) {
            return self::$speedSettingsCache[$userId];
        }

        try {
            $user = \App\Models\User::find($userId);
            if (!$user || !$user->plan_id) {
                self::$speedSettingsCache[$userId] = $defaults;
                return $defaults;
            }

            $plan = $user->userPlan;
            if (!$plan) {
                self::$speedSettingsCache[$userId] = $defaults;
                return $defaults;
            }

            // Speed settings based on plan slug - ULTRA OPTIMIZED for faster responses
            // PAID USERS GET PRIORITY: Lower timeout + faster connection + higher priority
            $settings = match($plan->slug) {
                'ultimate' => [
                    'max_tokens' => 4096,       // Maximum quality
                    'timeout' => 25,            // More time for complex responses
                    'connect_timeout' => 3,     // ULTRA FAST connection
                    'priority' => 'highest',    // TOP priority in queue
                    'use_best_model' => true,   // Always use best available model
                ],
                'pro' => [
                    'max_tokens' => 3072,       // High quality
                    'timeout' => 20,            // Fast timeout
                    'connect_timeout' => 3,     // Very fast connection
                    'priority' => 'high',       // High priority
                    'use_best_model' => true,   // Use best model for complex questions
                ],
                'lite' => [
                    'max_tokens' => 2048,       // Good quality
                    'timeout' => 18,            // Fast response
                    'connect_timeout' => 4,     // Fast connection (better than free)
                    'priority' => 'medium',     // Medium priority (above free)
                    'use_best_model' => false,  // Use standard model
                ],
                'starter' => [
                    'max_tokens' => 2048,
                    'timeout' => 18,
                    'connect_timeout' => 4,
                    'priority' => 'medium',
                    'use_best_model' => false,
                ],
                default => $defaults,          // Free plan uses defaults
            };

            self::$speedSettingsCache[$userId] = $settings;
            return $settings;
        } catch (\Exception $e) {
            Log::warning('Failed to get plan speed settings', ['error' => $e->getMessage()]);
            return $defaults;
        }
    }

    /**
     * Send chat message to selected AI model with streaming support
     *
     * ENHANCED with:
     * - Automatic failover system (Gemini → OpenAI)
     * - Adaptive model switching based on question complexity
     * - Plan-based feature gating
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
    public function chat(string $message, ?int $modelId = null, array $conversationHistory = [], ?callable $streamCallback = null, ?array $imageData = null, string $feature = 'chat', ?int $userId = null, string $language = 'english'): array
    {
        // Store language and feature for use in system prompt / routing
        $this->currentLanguage = $language;
        $this->currentFeature = $feature;
        $requestStartedAt = microtime(true);

        $parsed = $this->parseChatModePrefix($message);
        $message = $parsed['message'];

        $this->maybeAttachRetrievalContext($message, $feature, $userId, $parsed['mode']);

        // Get user's plan for feature gating
        $planSlug = $this->getUserPlanSlug($userId);

        // Get plan-based speed optimizations
        $speedSettings = $this->getPlanSpeedSettings($userId);

        // =====================================================================
        // STEP 1: Check for feature-specific AI model (highest priority)
        // This overrides failover/adaptive selection when admin sets a specific model
        // =====================================================================
        $featureModelId = $this->getFeatureSpecificModel($feature);
        $useFeatureModel = false;
        $aiModel = null;

        if ($featureModelId) {
            $aiModel = AiModel::where('id', $featureModelId)->where('is_active', true)->first();
            if ($aiModel && $this->isProviderEnabled($aiModel->provider)) {
                $useFeatureModel = true;
                Log::info('Using feature-specific model', [
                    'feature' => $feature,
                    'model' => $aiModel->name,
                    'provider' => $aiModel->provider,
                ]);
            } else {
                $aiModel = null;
            }
        }

        // =====================================================================
        // STEP 2: Check for user's explicitly selected model (second priority)
        // Only used if user manually selected a model in the app
        // =====================================================================
        if (!$aiModel && $modelId) {
            $tempModel = AiModel::where('id', $modelId)->where('is_active', true)->first();
            if ($tempModel && $this->isProviderEnabled($tempModel->provider)) {
                $aiModel = $tempModel;
                Log::info('Using user-selected model', [
                    'model' => $aiModel->name,
                    'provider' => $aiModel->provider,
                ]);
            }
        }

        // =====================================================================
        // STEP 3: Use FAILOVER + ADAPTIVE MODEL SELECTION
        // This is the main path for most requests
        // =====================================================================
        if (!$aiModel) {
            // Use the new failover system with adaptive model selection
            $result = $this->callWithFailover(
                $message,
                $conversationHistory,
                $streamCallback,
                $imageData,
                $feature,
                $userId,
                $speedSettings,
                $planSlug
            );

            // Track usage if successful and not streaming
            if ($result['success'] && !$streamCallback) {
                // Get the model that was actually used for tracking
                $usedModelId = $result['model'] ?? 'unknown';
                $usedProvider = $result['provider'] ?? 'unknown';
                $trackingModel = AiModel::where('model_identifier', $usedModelId)
                    ->orWhere('name', $usedModelId)
                    ->first();

                if ($trackingModel) {
                    $this->trackUsage($result, $trackingModel, $feature, $userId);
                }
            }

            // Update user's study tracking (streak, questions answered, etc.)
            if ($result['success']) {
                $this->updateUserStudyTracking($userId);
            }

            // Log failover usage for analytics
            if ($result['used_failover'] ?? false) {
                Log::info('Request completed via failover', [
                    'user_id' => $userId,
                    'plan' => $planSlug,
                    'primary_provider' => $result['primary_provider'] ?? 'unknown',
                    'failover_provider' => $result['failover_provider'] ?? 'unknown',
                ]);
            }

            $result['duration_ms'] = round((microtime(true) - $requestStartedAt) * 1000);

            return $result;
        }

        // =====================================================================
        // STEP 4: Direct call for feature-specific or user-selected model
        // No failover for these cases - use exactly what was specified
        // =====================================================================
        $apiKey = $this->getApiKey($aiModel->provider);

        if (!$apiKey) {
            return [
                'success' => false,
                'error' => ucfirst($aiModel->provider) . ' API key not configured. Please contact administrator.',
            ];
        }

        try {
            Log::info('AI Request - Direct call (feature/user selected)', [
                'provider' => $aiModel->provider,
                'model' => $aiModel->model_identifier,
                'user_id' => $userId,
                'plan' => $planSlug,
                'feature_specific' => $useFeatureModel,
            ]);

            $result = $this->executeProviderCall(
                $aiModel,
                $apiKey,
                $message,
                $conversationHistory,
                $streamCallback,
                $imageData,
                $feature,
                $speedSettings,
                $userId
            );

            // Track usage if successful and not streaming
            if ($result['success'] && !$streamCallback) {
                $this->trackUsage($result, $aiModel, $feature, $userId);
            }

            // Update user's study tracking (streak, questions answered, etc.)
            if ($result['success']) {
                $this->updateUserStudyTracking($userId);
            }

            $result['used_failover'] = false;
            $result['duration_ms'] = round((microtime(true) - $requestStartedAt) * 1000);

            return $result;

        } catch (\Exception $e) {
            Log::error('AI Service Error (Direct call)', [
                'provider' => $aiModel->provider,
                'model' => $aiModel->model_identifier,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to communicate with AI service. Please try again.',
                'used_failover' => false,
            ];
        }
    }

    /**
     * Check if provider is enabled in admin settings - CACHED for speed
     */
    protected function isProviderEnabled(string $provider): bool
    {
        // Check cache first
        if (isset(self::$providerEnabledCache[$provider])) {
            return self::$providerEnabledCache[$provider];
        }

        $enabledKey = match ($provider) {
            'openai' => 'ai.openai_enabled',
            'anthropic' => 'ai.claude_enabled',
            'deepseek' => 'ai.deepseek_enabled',
            'google' => 'ai.gemini_enabled',
            'xai' => 'ai.grok_enabled',
            default => null,
        };

        if (!$enabledKey) {
            self::$providerEnabledCache[$provider] = false;
            return false;
        }

        $enabled = FrontendConfig::getValue($enabledKey, '0') === '1';
        self::$providerEnabledCache[$provider] = $enabled;
        return $enabled;
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
     * Includes plan-based speed optimizations for premium users
     */
    protected function sendToOpenAI(AiModel $model, string $apiKey, string $message, array $history, ?callable $streamCallback, ?array $imageData = null, array $speedSettings = [], ?int $userId = null): array
    {
        $messages = [];

        // Get personalized system prompt with learning analytics (falls back to default if no user)
        $systemPrompt = $this->getPersonalizedSystemPrompt($userId, $message);

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

        // Apply plan-based speed settings
        $maxTokens = $speedSettings['max_tokens'] ?? $model->max_tokens ?? 2000;
        $timeout = $speedSettings['timeout'] ?? 30;

        $payload = [
            'model' => $model->model_identifier,
            'messages' => $messages,
            'max_tokens' => $maxTokens,
            'temperature' => $model->temperature ?? 0.7,
            'stream' => $streamCallback !== null,
        ];

        if ($streamCallback) {
            return $this->streamOpenAI($apiKey, $payload, $streamCallback);
        }

        $connectTimeout = $speedSettings['connect_timeout'] ?? 8;
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->withOptions(['verify' => config('app.env') !== 'local'])
          ->connectTimeout($connectTimeout)
          ->timeout($timeout)
          ->post($model->api_url, $payload);

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
     * Get system prompt from admin settings - CACHED for speed
     * STRICT NO-MARKDOWN formatting
     * RAG-ENHANCED for better educational responses
     */
    protected function getSystemPrompt(): string
    {
        // Return cached prompt if available (SPEED OPTIMIZATION)
        if (self::$systemPromptCache !== null) {
            return self::$systemPromptCache;
        }

        // Check if content filter is enabled
        $contentFilterEnabled = FrontendConfig::getValue('ai.content_filter_enabled', '0') === '1';

        // BLINKY - The Fun AI Tutor with Visual Icons
        self::$systemPromptCache = "You are **Blinky** - BlinkStudy's fun, friendly AI Tutor!

YOUR PERSONALITY:
- You're like that cool senior/bhaiya who makes studying FUN!
- Enthusiastic, encouraging, slightly playful
- You celebrate wins: \"Bahut badhiya!\", \"Sahi jawab!\"
- Use relatable Indian examples (cricket, movies, food)
- Patient - never make students feel dumb

VISUAL ICONS - MUST USE IN EVERY RESPONSE!
Use this syntax to add icons: <icon name=\"xyz\"/>

AVAILABLE ICONS:
• Physics: <icon name=\"sun\"/> <icon name=\"atom\"/> <icon name=\"magnet\"/> <icon name=\"wave\"/> <icon name=\"battery\"/> <icon name=\"energy\"/>
• Chemistry: <icon name=\"flask\"/> <icon name=\"molecule\"/> <icon name=\"water\"/> <icon name=\"fire\"/> <icon name=\"crystal\"/>
• Biology: <icon name=\"dna\"/> <icon name=\"cell\"/> <icon name=\"heart\"/> <icon name=\"plant\"/> <icon name=\"brain\"/> <icon name=\"leaf\"/>
• Maths: <icon name=\"graph\"/> <icon name=\"triangle\"/> <icon name=\"formula\"/> <icon name=\"calculator\"/> <icon name=\"infinity\"/>
• General: <icon name=\"bulb\"/> <icon name=\"star\"/> <icon name=\"warning\"/> <icon name=\"tick\"/> <icon name=\"arrow\"/>

RESPONSE FORMAT (MANDATORY):

**Example Response for 'What is photosynthesis?':**

<icon name=\"leaf\"/> **PHOTOSYNTHESIS**

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
• Photosynthesis sirf din mein hoti hai!

Samajh aaya? Koi doubt ho toh poocho! <icon name=\"bulb\"/>

---

FORMATTING RULES:
- Use **bold** for headings and key terms
- MUST use 3-5 icons per response using <icon name=\"xyz\"/> syntax
- Use • for bullet points
- Use 1. 2. 3. for steps
- Keep mobile-friendly short paragraphs
- Use Unicode: ², ³, ₂, →, √

TEACHING STYLE:
- Start with a hook/interesting fact
- Use analogies: \"Socho jaise...\"
- Break complex topics into chunks
- End with summary or memory trick
- Ask: \"Kuch aur poochna hai?\"

CATCHPHRASES:
- \"Bahut easy hai, dekho...\"
- \"Ek trick batata hoon...\"
- \"Exam mein ye zaroor aata hai!\"
- \"Real life example dekho...\"

LANGUAGE: Match the student's language!
- Hindi → Reply in Hindi
- Hinglish → Reply in Hinglish
- English → Reply in English

GREETING HANDLING (IMPORTANT!):
If user says hi/hii/hello/hey/namaste or any greeting:
- Reply with a SHORT friendly greeting (1-2 lines max)
- Ask what they want to learn today
- Example: \"Hey! 👋 Main Blinky hoon, tumhara AI tutor! Aaj kya padhna hai?\"
- DO NOT give educational content for greetings
- DO NOT explain any topic unless specifically asked

REMEMBER: You are BLINKY - make learning VISUAL and FUN with icons!";

        return self::$systemPromptCache;
    }

    /**
     * Clear cached data (call when settings change)
     */
    public static function clearCache(): void
    {
        self::$speedSettingsCache = [];
        self::$systemPromptCache = null;
        self::$providerEnabledCache = [];
        self::$userPlanSlugCache = [];
    }

    /**
     * Get personalized system prompt for a specific user
     * Uses AIPersonalityService for dynamic, context-aware prompts
     *
     * @param int|null $userId User ID
     * @param string|null $message Current message (for comprehensive analytics-based prompt)
     */
    public function getPersonalizedSystemPrompt(?int $userId, ?string $message = null): string
    {
        $languageInstruction = $this->getLanguageInstruction();
        $retrievalBlock = $this->formatRetrievalContextBlock();

        // Fast mode: skip heavy analytics DB work before AI call (GPT-like speed)
        if (config('ai.chat_fast_mode', true) && $this->isChatFeature($this->currentFeature)) {
            return $this->getSystemPrompt() . $retrievalBlock . "\n\n" . $languageInstruction;
        }

        // If no user, return default cached prompt with language instruction
        if (!$userId) {
            return $this->getSystemPrompt() . $retrievalBlock . "\n\n" . $languageInstruction;
        }

        try {
            $user = User::find($userId);
            if (!$user) {
                return $this->getSystemPrompt() . $retrievalBlock . "\n\n" . $languageInstruction;
            }

            // If message provided, use comprehensive prompt with learning analytics
            if ($message) {
                return AIPersonalityService::generateComprehensivePrompt($user, $message) . $retrievalBlock . "\n\n" . $languageInstruction;
            }

            // Otherwise use basic personalized prompt
            return AIPersonalityService::generateDynamicPrompt($user) . $retrievalBlock . "\n\n" . $languageInstruction;
        } catch (\Exception $e) {
            Log::warning('Failed to generate personalized prompt', ['error' => $e->getMessage()]);
            return $this->getSystemPrompt() . $retrievalBlock . "\n\n" . $languageInstruction;
        }
    }

    protected function maybeAttachRetrievalContext(string $message, string $feature, ?int $userId, string $mode = 'default'): void
    {
        $this->retrievalContext = '';
        $this->retrievalMeta = [];

        try {
            $settings = app(\App\Services\Retrieval\RetrievalSettingsService::class);
            if (! $settings->isHybridEnabled()) {
                return;
            }

            $user = $userId ? User::find($userId) : null;
            $metadata = [];
            if ($mode === 'search') {
                $metadata['force_route'] = 'exa_only';
            }

            $query = new \App\Services\Retrieval\DTO\RetrievalQuery(
                question: $message,
                classLevel: $user?->student_class,
                subject: null,
                userId: $userId,
                feature: $feature,
                metadata: $metadata,
            );

            $result = app(\App\Services\Retrieval\RetrievalOrchestrator::class)->retrieve($query);
            if ($result->success && $result->context !== '') {
                $this->retrievalContext = $result->context;
                $this->retrievalMeta = [
                    'provider' => $result->provider,
                    'sources' => $result->sources,
                ];

                Log::info('Hybrid retrieval attached', [
                    'provider' => $result->provider,
                    'mode' => $mode,
                    'sources' => count($result->sources),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Hybrid retrieval skipped', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Strip PromptInputBox mode prefixes ([Search: ...], [Think: ...], [Canvas: ...]).
     *
     * @return array{mode: string, message: string}
     */
    protected function parseChatModePrefix(string $message): array
    {
        if (preg_match('/^\[(Search|Think|Canvas):\s*(.+)\]$/su', trim($message), $matches)) {
            return [
                'mode' => strtolower($matches[1]),
                'message' => trim($matches[2]),
            ];
        }

        return ['mode' => 'default', 'message' => $message];
    }

    protected function formatRetrievalContextBlock(): string
    {
        if ($this->retrievalContext === '') {
            return '';
        }

        return "\n\nRETRIEVED KNOWLEDGE (use when relevant, cite sources when possible):\n"
            . $this->retrievalContext;
    }

    /**
     * Get language instruction based on user's preference
     */
    protected function getLanguageInstruction(): string
    {
        return match ($this->currentLanguage) {
            'hindi' => "🚨 CRITICAL LANGUAGE INSTRUCTION - MUST FOLLOW:
You MUST respond ONLY in Hindi using Devanagari script (हिन्दी लिपि).
❌ WRONG: 'Photosynthesis ek process hai jismein plants...'
✅ CORRECT: 'प्रकाश संश्लेषण एक प्रक्रिया है जिसमें पौधे...'

Rules:
1. Use ONLY Devanagari script (देवनागरी) - NOT Roman/English letters
2. Technical terms can be in English but explanation MUST be in हिन्दी
3. Example: 'Photosynthesis (प्रकाश संश्लेषण) एक प्रक्रिया है जिसमें पौधे सूर्य की रोशनी, पानी और कार्बन डाइऑक्साइड का उपयोग करके अपना भोजन बनाते हैं।'

Write your ENTIRE response in देवनागरी script!",
            'hinglish' => "IMPORTANT LANGUAGE INSTRUCTION: Respond in Hinglish - a natural mix of Hindi and English like Indian students speak. Use Roman script. Mix Hindi words with English naturally. Example: 'Bhai ye question easy hai, bas formula lagao aur solve karo.'",
            default => "Respond in English. You may use simple Hindi words like 'samjho', 'dekho' occasionally if it helps explain better to Indian students.",
        };
    }

    /**
     * Update user study tracking after AI interaction
     */
    public function updateUserStudyTracking(?int $userId): void
    {
        if (!$userId) {
            return;
        }

        try {
            $user = User::find($userId);
            if ($user) {
                AIPersonalityService::updateStudyTracking($user);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to update study tracking', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Stream OpenAI responses
     */
    protected function streamOpenAI(string $apiKey, array $payload, callable $callback): array
    {
        $fullContent = '';
        $buffer = '';
        $payload['stream'] = true;

        $ch = curl_init('https://api.openai.com/v1/chat/completions');

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_WRITEFUNCTION => function ($ch, $data) use ($callback, &$fullContent, &$buffer) {
                $buffer .= $data;

                while (($newlinePos = strpos($buffer, "\n")) !== false) {
                    $line = trim(substr($buffer, 0, $newlinePos));
                    $buffer = substr($buffer, $newlinePos + 1);

                    if (!str_starts_with($line, 'data: ')) {
                        continue;
                    }

                    $json = substr($line, 6);
                    if ($json === '[DONE]') {
                        continue;
                    }

                    $parsed = json_decode($json, true);
                    $delta = $parsed['choices'][0]['delta']['content'] ?? '';

                    if ($delta !== '') {
                        $fullContent .= $delta;
                        $callback($delta);
                    }
                }

                return strlen($data);
            },
        ]);

        curl_exec($ch);
        curl_close($ch);

        return [
            'success' => true,
            'content' => $fullContent,
            'streamed' => true,
            'provider' => 'openai',
        ];
    }

    /**
     * Send to Claude (Anthropic)
     * Includes plan-based speed optimizations for premium users
     */
    protected function sendToClaude(AiModel $model, string $apiKey, string $message, array $history, ?callable $streamCallback, ?array $imageData = null, array $speedSettings = [], ?int $userId = null): array
    {
        // Get personalized system prompt with learning analytics (falls back to default if no user)
        $systemPrompt = $this->getPersonalizedSystemPrompt($userId, $message);

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

        // Apply plan-based speed settings
        $maxTokens = $speedSettings['max_tokens'] ?? $model->max_tokens ?? 2000;
        $timeout = $speedSettings['timeout'] ?? 30;

        // Build payload
        $payload = [
            'model' => $model->model_identifier,
            'max_tokens' => $maxTokens,
            'messages' => $messages,
        ];

        // Add system prompt if available
        if ($systemPrompt) {
            $payload['system'] = $systemPrompt;
        }

        $connectTimeout = $speedSettings['connect_timeout'] ?? 8;
        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => '2023-06-01',
            'Content-Type' => 'application/json',
        ])->withOptions(['verify' => config('app.env') !== 'local'])
          ->connectTimeout($connectTimeout)
          ->timeout($timeout)
          ->post($model->api_url, $payload);

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
     * Includes plan-based speed optimizations for premium users
     */
    protected function sendToDeepSeek(AiModel $model, string $apiKey, string $message, array $history, ?callable $streamCallback, ?array $imageData = null, array $speedSettings = [], ?int $userId = null): array
    {
        // Get personalized system prompt with learning analytics (falls back to default if no user)
        $systemPrompt = $this->getPersonalizedSystemPrompt($userId, $message);

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

        // Apply plan-based speed settings
        $maxTokens = $speedSettings['max_tokens'] ?? $model->max_tokens ?? 2000;
        $timeout = $speedSettings['timeout'] ?? 30;

        $connectTimeout = $speedSettings['connect_timeout'] ?? 8;
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->withOptions(['verify' => config('app.env') !== 'local'])
          ->connectTimeout($connectTimeout)
          ->timeout($timeout)
          ->post($model->api_url, [
            'model' => $model->model_identifier,
            'messages' => $messages,
            'max_tokens' => $maxTokens,
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
     * Now includes plan-based speed optimizations
     */
    protected function sendToGemini(AiModel $model, string $apiKey, string $message, array $history, ?callable $streamCallback, ?array $imageData = null, string $feature = 'chat', array $speedSettings = [], ?int $userId = null): array
    {
        try {
            // Use the selected model directly (respect user's choice from mobile app)
            $chatModel = $model->model_identifier;

            // CRITICAL: Sanitize message to prevent UTF-8 encoding errors
            $message = $this->sanitizeUtf8($message);

            // Initialize GeminiService with feature and user tracking
            // Use provided userId, fallback to auth()->id() if not provided
            $effectiveUserId = $userId ?? auth()->id();
            $geminiService = new \App\Services\GeminiService(
                feature: $feature,
                modelName: $chatModel,
                userId: $effectiveUserId
            );

            // Set personalized system prompt with learning analytics if user is available
            if ($effectiveUserId) {
                $personalizedPrompt = $this->getPersonalizedSystemPrompt($effectiveUserId, $message);
                // Sanitize the system prompt as well
                $geminiService->setSystemPrompt($this->sanitizeUtf8($personalizedPrompt));
            }

            // Get thinking level for this feature
            $thinkingLevel = $this->getThinkingLevel($feature);

            // Build full prompt with history
            $fullPrompt = $message;

            // Add conversation history context if available
            if (!empty($history)) {
                $historyText = "Previous conversation:\n";
                foreach ($history as $msg) {
                    $role = $msg['role'] === 'assistant' ? 'Assistant' : 'User';
                    // CRITICAL: Sanitize each history message content
                    $content = $this->sanitizeUtf8($msg['content'] ?? '');
                    $historyText .= "{$role}: {$content}\n";
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

                // Optimize for speed: adjust max tokens based on feature and plan
                $planMaxTokens = $speedSettings['max_tokens'] ?? 2048;
                $planTimeout = $speedSettings['timeout'] ?? 60;

                $maxTokens = match($feature) {
                    'chat' => min($planMaxTokens, 2048),           // Fast chat responses
                    'pdf_solve' => min($planMaxTokens * 2, 4096),  // Medium for PDF solutions
                    'mcq_generation' => min($planMaxTokens * 3, 6144), // Longer for quiz generation
                    default => $planMaxTokens
                };

                $options = [
                    'temperature' => $model->temperature ?? 0.7,
                    'maxOutputTokens' => $maxTokens,
                    'timeout' => $planTimeout,
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
                // Regular text generation with plan-based optimizations
                $planMaxTokens = $speedSettings['max_tokens'] ?? 2048;
                $maxTokens = $feature === 'chat' ? min($planMaxTokens, 2048) : $planMaxTokens;

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
     * Includes UTF-8 sanitization to prevent encoding errors
     */
    protected function formatConversationHistory(array $history): array
    {
        $formatted = [];
        foreach ($history as $msg) {
            if (isset($msg['role']) && isset($msg['content'])) {
                $formatted[] = [
                    'role' => $msg['role'],
                    // Sanitize content to prevent UTF-8 encoding errors
                    'content' => $this->sanitizeUtf8($msg['content']),
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

    /**
     * Sanitize UTF-8 string to prevent json_encode errors
     * Critical for handling user input and conversation history with invalid UTF-8 sequences
     *
     * @param string $string Input string that may contain invalid UTF-8
     * @return string Cleaned UTF-8 string safe for json_encode
     */
    protected function sanitizeUtf8(string $string): string
    {
        // If string is empty, return as-is
        if (empty($string)) {
            return $string;
        }

        // Remove BOM if present
        $string = preg_replace('/^\xEF\xBB\xBF/', '', $string);

        // Remove null bytes
        $string = str_replace("\0", '', $string);

        // Use iconv to strip invalid UTF-8 sequences (most reliable method)
        $cleaned = @iconv('UTF-8', 'UTF-8//IGNORE', $string);
        if ($cleaned !== false) {
            $string = $cleaned;
        }

        // If still not valid UTF-8, try to detect and convert encoding
        if (!mb_check_encoding($string, 'UTF-8')) {
            $encoding = mb_detect_encoding($string, ['UTF-8', 'ISO-8859-1', 'Windows-1252', 'ASCII'], true);
            if ($encoding && $encoding !== 'UTF-8') {
                $converted = @mb_convert_encoding($string, 'UTF-8', $encoding);
                if ($converted !== false) {
                    $string = $converted;
                }
            }
        }

        // Remove control characters except newline, tab, carriage return
        $string = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $string);

        // Remove Unicode replacement characters that indicate encoding errors
        $string = str_replace("\xEF\xBF\xBD", '', $string);

        // Final safety check - if json_encode fails, do aggressive cleanup
        if (json_encode($string) === false) {
            // Remove all non-UTF-8 characters by encoding/decoding
            $string = mb_convert_encoding($string, 'UTF-8', 'UTF-8');
            // If still failing, strip to ASCII
            if (json_encode($string) === false) {
                $string = preg_replace('/[^\x20-\x7E\n\r\t]/', '', $string);
            }
        }

        return $string;
    }
}
