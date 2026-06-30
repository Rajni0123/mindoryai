<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Support\ApiValidator;
use App\Http\Controllers\AIChatController;
use App\Http\Controllers\MobileChatController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    try {
        $user = $request->user();
        $usageService = app(\App\Services\UsageLimitService::class);

        $userData = \App\Support\ApiResponseSanitizer::userProfile($user);

        // Add plan information
        if ($user->plan_id) {
            $plan = \App\Models\UserPlan::find($user->plan_id);
            if ($plan) {
                $features = $plan->features;
                if (is_string($features)) $features = json_decode($features, true);
                if (is_string($features)) $features = json_decode($features, true);

                $userData['plan'] = [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'slug' => $plan->slug,
                    'validity_days' => $plan->validity_days,
                ];
            } else {
                $userData['plan'] = ['id' => null, 'name' => null, 'slug' => null, 'requires_subscription' => true];
            }
        } else {
            $userData['plan'] = ['id' => null, 'name' => null, 'slug' => null, 'requires_subscription' => true];
        }

        // Add usage summary and plan config
        $userData['usage'] = $usageService->getUsageSummary($user);
        $userData['plan_config'] = $usageService->getPlanConfig($user);

        return response()->json($userData);
    } catch (\Exception $e) {
        \Log::error('Error in /user endpoint', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
        return response()->json([
            'error' => 'Failed to fetch user data',
            'message' => config('app.debug') ? $e->getMessage() : 'Please try again later',
        ], 500);
    }
});

// ========================================
// HEALTH CHECK ENDPOINT
// ========================================

// Health check endpoint (PUBLIC - for mobile app and monitoring)
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'API is working',
        'timestamp' => now()->toIso8601String(),
        'version' => config('app.version', '1.0.0'),
    ]);
});

// ========================================
// USER PROFILE & CREDITS MANAGEMENT
// ========================================

// Get user profile with usage limits
Route::middleware('auth:sanctum')->get('/user/profile', function (Request $request) {
    try {
        $user = $request->user();
        $usageService = app(\App\Services\UsageLimitService::class);

        // Get plan information
        $planData = ['id' => null, 'name' => null, 'slug' => null, 'requires_subscription' => true];
        if ($user->plan_id) {
            $plan = \App\Models\UserPlan::find($user->plan_id);
            if ($plan) {
                $planData = [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'slug' => $plan->slug,
                    'validity_days' => $plan->validity_days,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'mobile' => $user->mobile,
                'role' => $user->role,
                'plan_id' => $user->plan_id,
                'plan' => $planData,
                'plan_expires_at' => $user->plan_expires_at,
                'is_active' => $user->is_active,
                'created_at' => $user->created_at,
            ],
            'usage' => $usageService->getUsageSummary($user),
            'plan_config' => $usageService->getPlanConfig($user),
        ]);
    } catch (\Exception $e) {
        $user = $request->user();

        // Get plan information even in error case
        $planData = ['id' => null, 'name' => null, 'slug' => null, 'requires_subscription' => true];
        if ($user->plan_id) {
            $plan = \App\Models\UserPlan::find($user->plan_id);
            if ($plan) {
                $planData = [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'slug' => $plan->slug,
                    'validity_days' => $plan->validity_days,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'mobile' => $user->mobile,
                'role' => $user->role,
                'plan_id' => $user->plan_id,
                'plan' => $planData,
                'plan_expires_at' => $user->plan_expires_at,
                'is_active' => $user->is_active,
                'created_at' => $user->created_at,
            ],
            'usage' => [],
            'plan_config' => [],
        ]);
    }
});

// Update user name
Route::middleware('auth:sanctum')->put('/user/update-name', function (Request $request) {
    $validated = ApiValidator::validate($request, [
        'name' => ApiValidator::safeString(config('api-validation.limits.name_max', 50), true),
    ]);
    $validated['name'] = trim($validated['name']);
    if (strlen($validated['name']) < config('api-validation.limits.name_min', 2)) {
        ApiValidator::throwResponse(['name' => ['Name must be at least 2 characters.']]);
    }

    $user = $request->user();
    $user->name = $validated['name'];
    $user->save();

    return response()->json([
        'success' => true,
        'message' => 'Name updated successfully',
        'user' => $user,
    ]);
});

// Update user mobile (for email login users)
Route::middleware('auth:sanctum')->put('/user/update-mobile', function (Request $request) {
    $validated = ApiValidator::validate($request, [
        'mobile' => ApiValidator::mobileIndia() . '|unique:users,mobile,' . $request->user()->id,
    ]);

    $user = $request->user();
    $user->mobile = $validated['mobile'];
    $user->save();

    return response()->json([
        'success' => true,
        'message' => 'Mobile number updated successfully',
        'user' => $user,
    ]);
});

// Complete profile (for first-time login / study setup)
Route::middleware('auth:sanctum')->post('/user/complete-profile', function (Request $request) {
    $validated = ApiValidator::validate($request, [
        'name' => ApiValidator::safeString(config('api-validation.limits.name_max', 50), true),
        'mobile' => 'nullable|digits:10|unique:users,mobile,' . $request->user()->id,
        'target_exam' => ApiValidator::safeString(50, false),
        'student_class' => ApiValidator::inConfig('student_classes'),
        'subjects' => ApiValidator::safeString(30, false),
        'favorite_subject' => ApiValidator::safeString(30, false),
        'exam_date' => 'nullable|date|after:today',
    ]);
    if (strlen(trim($validated['name'])) < config('api-validation.limits.name_min', 2)) {
        ApiValidator::throwResponse(['name' => ['Name must be at least 2 characters.']]);
    }

    $user = $request->user();
    $user->name = $validated['name'];
    $user->is_profile_complete = true;
    $user->profile_completed = true;
    $user->profile_completed_at = now();

    if (isset($validated['mobile'])) {
        $user->mobile = $validated['mobile'];
    }
    if (!empty($validated['target_exam'])) {
        $user->target_exam = $validated['target_exam'];
    }
    if (!empty($validated['student_class'])) {
        $user->student_class = $validated['student_class'];
    }
    $subjects = $validated['subjects'] ?? $validated['favorite_subject'] ?? null;
    if (!empty($subjects)) {
        $user->favorite_subject = $subjects;
    }
    if (!empty($validated['exam_date'])) {
        $user->exam_date = $validated['exam_date'];
    }

    $user->save();

    return response()->json([
        'success' => true,
        'message' => 'Profile completed successfully',
        'user' => $user->fresh(),
        'is_profile_complete' => true,
    ]);
});

Route::middleware('auth:sanctum')->get('/user/badges', [\App\Http\Controllers\Api\BadgeController::class, 'index']);

// Update profile (name and/or mobile)
Route::middleware('auth:sanctum')->put('/user/profile', function (Request $request) {
    try {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        $validated = ApiValidator::validate($request, [
            'name' => ApiValidator::safeString(config('api-validation.limits.name_max', 50), false),
            'mobile' => 'nullable|digits:10|unique:users,mobile,' . $user->id,
            'target_exam' => ApiValidator::safeString(50, false),
            'student_class' => ApiValidator::inConfig('student_classes'),
            'subjects' => ApiValidator::safeString(30, false),
            'favorite_subject' => ApiValidator::safeString(30, false),
            'exam_date' => 'nullable|date|after:today',
        ]);

        $updated = false;

        if (isset($validated['name']) && $validated['name']) {
            if (strlen(trim($validated['name'])) < config('api-validation.limits.name_min', 2)) {
                ApiValidator::throwResponse(['name' => ['Name must be at least 2 characters.']]);
            }
            $user->name = trim($validated['name']);
            $user->is_profile_complete = true;
            $user->profile_completed = true;
            $user->profile_completed_at = now();
            $updated = true;
        }

        if (!empty($validated['target_exam'])) {
            $user->target_exam = $validated['target_exam'];
            $updated = true;
        }
        if (!empty($validated['student_class'])) {
            $user->student_class = $validated['student_class'];
            $updated = true;
        }
        $subjects = $validated['subjects'] ?? $validated['favorite_subject'] ?? null;
        if (!empty($subjects)) {
            $user->favorite_subject = $subjects;
            $updated = true;
        }
        if (!empty($validated['exam_date'])) {
            $user->exam_date = $validated['exam_date'];
            $updated = true;
        }

        if (isset($validated['mobile']) && $validated['mobile']) {
            // Check if mobile is different from current
            if ($user->mobile !== $validated['mobile']) {
                $user->mobile = $validated['mobile'];
                $user->mobile_verified_at = null; // Reset verification if mobile changed
                $updated = true;
            }
        }

        if ($updated) {
            $user->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'is_profile_complete' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'mobile' => $user->mobile,
                'role' => $user->role,
                'plan_id' => $user->plan_id,
                'is_active' => $user->is_active,
                'is_profile_complete' => (bool) $user->is_profile_complete,
                'student_class' => $user->student_class,
                'target_exam' => $user->target_exam,
                'subjects' => $user->favorite_subject,
                'favorite_subject' => $user->favorite_subject,
            ],
        ]);
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => collect($e->errors())->flatten()->first() ?? 'Validation failed',
            'errors' => $e->errors(),
        ], 422);
    } catch (\Exception $e) {
        \Log::error('Profile update failed', [
            'user_id' => $request->user()->id ?? null,
            'error' => $e->getMessage(),
        ]);
        return response()->json([
            'success' => false,
            'message' => 'Failed to update profile. Please try again.',
        ], 500);
    }
});

// Get usage limits and summary
Route::middleware('auth:sanctum')->get('/usage/summary', function (Request $request) {
    $usageService = app(\App\Services\UsageLimitService::class);
    $user = $request->user();

    return response()->json([
        'success' => true,
        'usage' => $usageService->getUsageSummary($user),
        'plan_config' => $usageService->getPlanConfig($user),
    ]);
});

// Check if a specific feature can be used
Route::middleware('auth:sanctum')->get('/usage/check/{feature}', function (Request $request, string $feature) {
    \App\Support\ApiValidator::validateRoute($request, ['feature' => $feature], [
        'feature' => \App\Support\ApiValidator::requiredInConfig('features'),
    ]);
    $usageService = app(\App\Services\UsageLimitService::class);
    $user = $request->user();

    return response()->json(
        $usageService->canUse($user, $feature)
    );
});

// Credits Balance (compatibility endpoint - maps usage limits to credits format)
Route::middleware('auth:sanctum')->get('/credits/balance', function (Request $request) {
    $usageService = app(\App\Services\UsageLimitService::class);
    $user = $request->user();
    $usage = $usageService->getUsageSummary($user);
    $planConfig = $usageService->getPlanConfig($user);

    // Map usage limits to a credits-like response for mobile app compatibility
    $totalCredits = 0;
    $usedCredits = 0;

    foreach ($usage as $feature => $data) {
        if (is_array($data)) {
            $limit = $data['limit'] ?? 0;
            $used = $data['used'] ?? 0;
            if ($limit === 'unlimited') {
                $totalCredits += 9999;
                $usedCredits += $used;
            } else {
                $totalCredits += (int) $limit;
                $usedCredits += (int) $used;
            }
        }
    }

    return response()->json([
        'success' => true,
        'balance' => max(0, $totalCredits - $usedCredits),
        'total' => $totalCredits,
        'used' => $usedCredits,
        'unlimited' => $user->role === 'admin' || ($planConfig['is_unlimited'] ?? false),
        'usage' => $usage,
        'plan_config' => $planConfig,
    ]);
});

// Ad Reward - disabled (no free tier; subscription required for features)
Route::middleware('auth:sanctum')->post('/credits/ad-reward', function (Request $request) {
    return response()->json([
        'success' => false,
        'message' => 'Ad rewards are not available. Please subscribe to a plan.',
        'requires_subscription' => true,
    ], 403);
});

// User Payment History
Route::middleware('auth:sanctum')->get('/payments', function (Request $request) {
    $user = $request->user();

    $payments = \App\Models\Payment::with('plan')
        ->where('user_id', $user->id)
        ->orderBy('created_at', 'desc')
        ->get()
        ->map(function ($payment) {
            return [
                'id' => $payment->id,
                'transaction_id' => $payment->transaction_id,
                'amount' => (float) $payment->amount,
                'status' => $payment->status,
                'payment_method' => $payment->payment_method,
                'plan_name' => $payment->plan?->name ?? 'N/A',
                'created_at' => $payment->created_at->toISOString(),
                'verified_at' => $payment->verified_at?->toISOString(),
            ];
        });

    return response()->json([
        'success' => true,
        'payments' => $payments,
    ]);
});

// ========================================
// MOBILE APP AUTHENTICATION (OTP-based) — 5 req / 15 min per IP
// ========================================
Route::middleware('throttle:auth')->group(function () {
    Route::post('/login/send-otp', [\App\Http\Controllers\Auth\UnifiedLoginController::class, 'sendOTP']);
    Route::post('/login/verify-otp', [\App\Http\Controllers\Auth\UnifiedLoginController::class, 'verifyOTP']);

    // Legacy aliases kept for older app builds
    Route::post('/auth/send-otp', [\App\Http\Controllers\Auth\UnifiedLoginController::class, 'sendOTP']);
    Route::post('/auth/verify-otp', [\App\Http\Controllers\Auth\UnifiedLoginController::class, 'verifyOTP']);

    // Email OTP Login Routes
    Route::post('/login/send-email-otp', [\App\Http\Controllers\Auth\UnifiedLoginController::class, 'sendEmailOTP']);
    Route::post('/login/verify-email-otp', [\App\Http\Controllers\Auth\UnifiedLoginController::class, 'verifyEmailOTP']);

    // WhatsApp OTP Login Routes
    Route::post('/login/send-whatsapp-otp', [\App\Http\Controllers\Auth\UnifiedLoginController::class, 'sendWhatsAppOTP']);

    // Google Sign-In Route
    Route::post('/login/google', [\App\Http\Controllers\Auth\UnifiedLoginController::class, 'googleSignIn']);
});

Route::post('/logout', [\App\Http\Controllers\Auth\UnifiedLoginController::class, 'logout'])->middleware('auth:sanctum');

// Rotate Sanctum bearer token (mobile clients). Web SPAs use httpOnly session cookies.
Route::middleware('auth:sanctum')->post('/auth/refresh', [\App\Http\Controllers\Api\AuthSessionController::class, 'refreshToken']);

// AI Models API - Returns only active models from ENABLED providers
Route::get('/ai-models', function () {
    try {
        // Get enabled providers from admin panel
        $enabledProviders = [];

        if (\App\Models\FrontendConfig::getValue('ai.openai_enabled', '0') === '1') {
            $enabledProviders[] = 'openai';
        }
        if (\App\Models\FrontendConfig::getValue('ai.anthropic_enabled', '0') === '1' ||
            \App\Models\FrontendConfig::getValue('ai.claude_enabled', '0') === '1') {
            $enabledProviders[] = 'anthropic';
        }
        if (\App\Models\FrontendConfig::getValue('ai.deepseek_enabled', '0') === '1') {
            $enabledProviders[] = 'deepseek';
        }
        if (\App\Models\FrontendConfig::getValue('ai.google_enabled', '0') === '1' ||
            \App\Models\FrontendConfig::getValue('ai.gemini_enabled', '0') === '1') {
            $enabledProviders[] = 'google';
        }
        if (\App\Models\FrontendConfig::getValue('ai.xai_enabled', '0') === '1' ||
            \App\Models\FrontendConfig::getValue('ai.grok_enabled', '0') === '1') {
            $enabledProviders[] = 'xai';
        }

        // Only get models that are BOTH active AND from enabled providers
        $activeModels = \App\Models\AiModel::where('is_active', true)
            ->whereIn('provider', $enabledProviders)
            ->orderBy('order')
            ->orderBy('name')
            ->get()
            ->map(function($model) {
                return [
                    'id' => $model->id,
                    'name' => $model->model_identifier,
                    'display_name' => $model->name,
                    'description' => $model->description,
                    'provider' => $model->provider,
                    'icon' => $model->icon ? asset($model->icon) : null,
                    'color' => $model->color,
                    'is_active' => true,
                ];
            });

        return response()->json($activeModels);
    } catch (\Exception $e) {
        // Fallback if database not available - return empty array
        return response()->json([]);
    }
});

// Chat API Routes
Route::middleware('auth:sanctum')->group(function () {
    // Get all chats
    Route::get('/chats', [MobileChatController::class, 'getChats']);

    // Create new chat
    Route::post('/chats', [MobileChatController::class, 'createChat']);

    // Get chat messages
    Route::get('/chats/{chatId}/messages', [MobileChatController::class, 'getMessages']);

    // Send message - non-streaming JSON (add ?stream=1 for GPT-like instant tokens)
    Route::post('/chats/{chatId}/messages', [MobileChatController::class, 'sendMessage'])
        ->middleware(['check.feature:chat', 'throttle:ai', 'throttle:upload']);

    // Streaming chat — first token in ~1-2 seconds like ChatGPT
    Route::post('/chats/{chatId}/messages/stream', [MobileChatController::class, 'sendMessageStream'])
        ->middleware(['check.feature:chat', 'throttle:ai', 'throttle:upload']);

    // Delete chat
    Route::delete('/chats/{chatId}', [MobileChatController::class, 'deleteChat']);

    // Rename chat
    Route::put('/chats/{chatId}', [MobileChatController::class, 'renameChat']);

    // Feedback for AI optimization
    Route::post('/feedback', [MobileChatController::class, 'saveFeedback']);

    // Voice transcription and summarization
    Route::post('/voice/transcribe', [\App\Http\Controllers\VoiceController::class, 'transcribe'])
        ->middleware(['throttle:ai', 'throttle:upload']);
    Route::post('/voice/summarize', [\App\Http\Controllers\VoiceController::class, 'summarize'])
        ->middleware('throttle:ai');

    // Quiz generation from image with caching
    Route::post('/quiz/generate-from-image', [\App\Http\Controllers\QuizController::class, 'generateFromImage'])
        ->middleware(['check.feature:video_quiz', 'throttle:ai', 'throttle:upload']);

    // Quiz generation by topic (without image)
    Route::get('/quiz/generate-by-topic', [\App\Http\Controllers\QuizController::class, 'generateByTopic'])
        ->middleware(['check.feature:topic_quiz', 'throttle:ai']);

    // Reasoning quiz generation
    Route::get('/quiz/generate-reasoning', [\App\Http\Controllers\QuizController::class, 'generateReasoningQuiz'])
        ->middleware(['check.feature:topic_quiz', 'throttle:ai']);

    // Quiz PDF download - download generated quiz as PDF
    Route::post('/quiz/download-pdf', [\App\Http\Controllers\QuizController::class, 'downloadQuizPdf']);
    Route::post('/quiz/download-answer-key', [\App\Http\Controllers\QuizController::class, 'downloadAnswerKeyPdf']);

    // Notification routes
    Route::get('/notifications', [\App\Http\Controllers\Api\NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [\App\Http\Controllers\Api\NotificationController::class, 'unreadCount']);
    Route::post('/notifications/{id}/mark-read', [\App\Http\Controllers\Api\NotificationController::class, 'markAsRead']);
    Route::post('/notifications/mark-all-read', [\App\Http\Controllers\Api\NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/{id}', [\App\Http\Controllers\Api\NotificationController::class, 'destroy']);

    // Quiz routes (continued below in file)
});

// App Configuration (synced with admin panel) - PUBLIC endpoint
Route::get('/app-config', function () {
    try {
        // Get features from DynamicAppConfig (synced with admin Mobile App Config panel)
        $features = [
            'chat' => (bool) \App\Models\DynamicAppConfig::getValue('features.chat_enabled', true),
            'imageUpload' => (bool) \App\Models\DynamicAppConfig::getValue('features.image_upload', true),
            'fileUpload' => (bool) \App\Models\DynamicAppConfig::getValue('features.pdf_upload', true),
            'voiceInput' => (bool) \App\Models\DynamicAppConfig::getValue('features.voice_input', true),
            'multipleModels' => true,
            'shareChat' => (bool) \App\Models\DynamicAppConfig::getValue('features.share_chat', true),
            'pdfUpload' => (bool) \App\Models\DynamicAppConfig::getValue('features.pdf_upload', true),
            'imageAnalysis' => (bool) \App\Models\DynamicAppConfig::getValue('features.quiz_from_image', true),
            'notebook' => (bool) \App\Models\DynamicAppConfig::getValue('features.notebook', true),
            'referralSystem' => (bool) \App\Models\DynamicAppConfig::getValue('features.referral_system', true),
        ];

        // Get screen visibility from DynamicAppConfig (synced with admin panel)
        $screens = [
            'chat' => (bool) \App\Models\DynamicAppConfig::getValue('features.chat_enabled', true),
            'settings' => true,
            'profile' => true,
            'plans' => true,
            'notebook' => (bool) \App\Models\DynamicAppConfig::getValue('features.notebook', true),
        ];

        // Get logo/icon/splash URLs
        $appLogo = \App\Models\FrontendConfig::getValue('mobile.app_logo');
        $appIcon = \App\Models\FrontendConfig::getValue('mobile.app_icon');
        $loginLogo = \App\Models\FrontendConfig::getValue('auth.login.logo');
        $splashScreen = \App\Models\FrontendConfig::getValue('mobile.splash_screen');

        // Get authentication settings
        $googleWebClientId = config('services.google.client_id');
        $googleAndroidClientId = config('services.google.android_client_id');
        $googleIosClientId = config('services.google.ios_client_id');
        $googleEnabledInAdmin = (bool) \App\Models\FrontendConfig::getValue('auth.social.google.enabled', false);
        $googleEnabledOnWeb = (bool) \App\Models\SystemSetting::get('auth.google_login_enabled', true);
        $googleClientId = \App\Models\FrontendConfig::getValue('auth.social.google.client_id') ?: $googleWebClientId;
        $googleEnabled = $googleEnabledInAdmin || ($googleEnabledOnWeb && !empty($googleWebClientId));
        $googleRedirectUrl = \App\Models\FrontendConfig::getValue('auth.social.google.redirect_url')
            ?: config('services.google.redirect')
            ?: rtrim((string) config('domains.main_url', config('app.url')), '/') . '/auth/google/callback';
        if (str_contains((string) $googleRedirectUrl, 'api.blinkstudy.in')) {
            $googleRedirectUrl = rtrim((string) config('domains.main_url', 'https://blinkstudy.in'), '/') . '/auth/google/callback';
        }

        $authConfig = [
            'otp' => [
                'enabled' => (bool) \App\Models\FrontendConfig::getValue('auth.otp.enabled', true),
                'length' => (int) \App\Models\FrontendConfig::getValue('auth.otp.length', 6),
                'expiryMinutes' => (int) \App\Models\FrontendConfig::getValue('auth.otp.expiry_minutes', 5),
                'resendCooldown' => (int) \App\Models\FrontendConfig::getValue('auth.otp.resend_cooldown', 60),
                'maxAttempts' => (int) \App\Models\FrontendConfig::getValue('auth.otp.max_attempts', 3),
            ],
            'emailOtp' => [
                'enabled' => (bool) \App\Models\FrontendConfig::getValue('auth.email_otp.enabled', true),
                'length' => (int) \App\Models\FrontendConfig::getValue('auth.email_otp.length', 6),
                'expiryMinutes' => (int) \App\Models\FrontendConfig::getValue('auth.email_otp.expiry_minutes', 5),
                'resendCooldown' => (int) \App\Models\FrontendConfig::getValue('auth.email_otp.resend_cooldown', 60),
                'maxAttempts' => (int) \App\Models\FrontendConfig::getValue('auth.email_otp.max_attempts', 3),
            ],
            'login' => [
                'title' => \App\Models\FrontendConfig::getValue('auth.login.title', 'Welcome Back!'),
                'subtitle' => \App\Models\FrontendConfig::getValue('auth.login.subtitle', 'Sign in to continue to your AI assistant'),
                'logo' => $loginLogo && str_starts_with($loginLogo, 'http') ? $loginLogo : ($loginLogo ? url($loginLogo) : null),
                'backgroundColor' => \App\Models\FrontendConfig::getValue('auth.login.background_color', '#0a0a0a'),
                'primaryColor' => \App\Models\FrontendConfig::getValue('auth.login.primary_color', '#0df259'),
                'textColor' => \App\Models\FrontendConfig::getValue('auth.login.text_color', '#ffffff'),
                'phonePlaceholder' => \App\Models\FrontendConfig::getValue('auth.login.phone_placeholder', 'Enter your mobile number'),
                'emailPlaceholder' => \App\Models\FrontendConfig::getValue('auth.login.email_placeholder', 'Enter your email address'),
                'buttonText' => \App\Models\FrontendConfig::getValue('auth.login.button_text', 'Send OTP'),
                'termsText' => \App\Models\FrontendConfig::getValue('auth.login.terms_text', 'By continuing, you agree to our Terms of Service and Privacy Policy'),
                'footerText' => \App\Models\FrontendConfig::getValue('auth.login.footer_text', 'Need help? Contact ' . config('services.support_email')),
            ],
            'social' => [
                'otpLoginEnabled' => (bool) \App\Models\FrontendConfig::getValue('auth.otp_login_enabled', true),
                'emailOtpLoginEnabled' => (bool) \App\Models\FrontendConfig::getValue('auth.email_otp_login_enabled', true),
                'whatsappOtpEnabled' => (bool) \App\Models\FrontendConfig::getValue('auth.social.whatsapp_otp_enabled', false),
                'googleEnabled' => $googleEnabled,
                'googleClientId' => $googleClientId,
                'googleWebClientId' => $googleWebClientId,
                'googleAndroidClientId' => $googleAndroidClientId,
                'googleIosClientId' => $googleIosClientId,
                'googleRedirectUrl' => $googleRedirectUrl,
                'appleEnabled' => (bool) \App\Models\FrontendConfig::getValue('auth.social.apple.enabled', false),
                'facebookEnabled' => (bool) \App\Models\FrontendConfig::getValue('auth.social.facebook.enabled', false),
                'dividerText' => \App\Models\FrontendConfig::getValue('auth.social.divider_text', 'OR'),
            ],
            'security' => [
                'maxDevices' => (int) \App\Models\FrontendConfig::getValue('auth.security.max_devices', 5),
                'sessionTimeout' => (int) \App\Models\FrontendConfig::getValue('auth.security.session_timeout', 1440),
                'requirePhoneVerification' => (bool) \App\Models\FrontendConfig::getValue('auth.security.require_phone_verification', true),
            ],
        ];

        // Usage limit system (no credits)
        $usageLimitConfig = [
            'enabled' => true,
            'daily_reset' => '00:00',
        ];

        // Get profile configuration
        $profileConfig = [
            'requireName' => (bool) \App\Models\FrontendConfig::getValue('profile.require_name', true),
            'requireMobileForEmailLogin' => (bool) \App\Models\FrontendConfig::getValue('profile.require_mobile_for_email_login', false),
            'showUpgradeOption' => (bool) \App\Models\FrontendConfig::getValue('profile.show_upgrade_option', true),
        ];

        // Get active exams for app
        $exams = [];
        try {
            $exams = \App\Models\Exam::where('is_active', true)
                ->orderBy('order')
                ->get(['id', 'name', 'slug', 'description', 'category', 'level', 'subjects', 'icon'])
                ->map(function ($exam) {
                    return [
                        'id' => $exam->id,
                        'name' => $exam->name,
                        'slug' => $exam->slug,
                        'description' => $exam->description,
                        'category' => $exam->category,
                        'level' => $exam->level,
                        'subjects' => $exam->subjects,
                        'icon' => $exam->icon,
                    ];
                });
        } catch (\Exception $e) {
            // Exams table may not exist yet
        }

        // Get pricing plans (synced with admin panel) — paid plans only
        $plans = DB::table('user_plans')
            ->where('is_active', true)
            ->where('slug', '!=', 'free')
            ->where('price', '>', 0)
            ->orderBy('order')
            ->get()
            ->map(function ($plan) {
                $features = json_decode($plan->features, true);
                $featureList = $features['features'] ?? []; // ✅ Get features from correct key
                $isPopular = !empty($features['popular']) || !empty($features['recommended']);

                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'slug' => $plan->slug,
                    'description' => $plan->description,
                    'price' => number_format($plan->price),
                    'period' => $plan->billing_period ?? 'month', // month or year
                    'billing' => $plan->billing_description,
                    'savings' => $features['savings'] ?? null,
                    'popular' => $isPopular, // Add popular flag from features data
                    'features' => $featureList, // ✅ INCLUDE FEATURES ARRAY
                    'ads' => (bool) ($features['ads'] ?? true), // Whether this plan shows ads
                ];
            });

        // Get policies (synced with admin panel)
        $policies = \App\Models\Policy::enabled()
            ->ordered()
            ->get(['key', 'title', 'content'])
            ->map(function ($policy) {
                return [
                    'key' => $policy->key,
                    'title' => $policy->title,
                    'content' => $policy->content,
                ];
            });

        // Get logo and icon with proper absolute URLs
        $appLogo = \App\Models\FrontendConfig::getValue('mobile.app_logo');
        $appIcon = \App\Models\FrontendConfig::getValue('mobile.app_icon');

        // Convert relative paths to absolute URLs if needed
        if ($appLogo && !str_starts_with($appLogo, 'http')) {
            $appLogo = url($appLogo);
        }
        if ($appIcon && !str_starts_with($appIcon, 'http')) {
            $appIcon = url($appIcon);
        }
        if ($splashScreen && !str_starts_with($splashScreen, 'http')) {
            $splashScreen = url($splashScreen);
        }

        // App update settings from DynamicAppConfig (synced with admin panel)
        $latestVersion = \App\Models\DynamicAppConfig::getValue('app.latest_version', '1.0.0');
        $minSupportedVersion = \App\Models\DynamicAppConfig::getValue('app.min_supported_version', '1.0.0');
        $forceUpdate = (bool) \App\Models\DynamicAppConfig::getValue('app.force_update', false);
        $updateMessage = \App\Models\DynamicAppConfig::getValue('app.update_message', 'A new version is available. Please update to continue.');
        $updateUrlAndroid = \App\Models\DynamicAppConfig::getValue('app.update_url_android');
        $updateUrlIos = \App\Models\DynamicAppConfig::getValue('app.update_url_ios');

        // Credit system from DynamicAppConfig (synced with admin panel)
        $credits = [
            'free_tier_messages' => (int) \App\Models\DynamicAppConfig::getValue('credits.free_tier_messages', 50),
            'referral_reward' => (int) \App\Models\DynamicAppConfig::getValue('credits.referral_reward', 3),
            'signup_bonus' => (int) \App\Models\DynamicAppConfig::getValue('credits.signup_bonus', 3),
        ];

        // Content settings from DynamicAppConfig (synced with admin panel)
        $content = [
            'welcome_message' => \App\Models\DynamicAppConfig::getValue('content.welcome_message'),
            'help_url' => \App\Models\DynamicAppConfig::getValue('content.help_url'),
            'privacy_policy_url' => \App\Models\DynamicAppConfig::getValue('content.privacy_policy_url'),
            'terms_url' => \App\Models\DynamicAppConfig::getValue('content.terms_url'),
        ];

        // AI settings from DynamicAppConfig (synced with admin panel)
        $aiSettings = [
            'default_model' => \App\Models\DynamicAppConfig::getValue('ai.default_model', 'gpt-4o-mini'),
            'max_tokens' => (int) \App\Models\DynamicAppConfig::getValue('ai.max_tokens', 2000),
            'timeout' => (int) \App\Models\DynamicAppConfig::getValue('ai.timeout_seconds', 30),
        ];

        // Ads settings from DynamicAppConfig (synced with admin panel)
        $adsConfig = [
            'enabled' => filter_var(\App\Models\DynamicAppConfig::getValue('ads.enabled', false), FILTER_VALIDATE_BOOLEAN),
            'admob_app_id_android' => \App\Models\DynamicAppConfig::getValue('ads.admob_app_id_android', ''),
            'admob_app_id_ios' => \App\Models\DynamicAppConfig::getValue('ads.admob_app_id_ios', ''),
            'banner' => [
                'enabled' => filter_var(\App\Models\DynamicAppConfig::getValue('ads.banner_enabled', false), FILTER_VALIDATE_BOOLEAN),
                'android_unit_id' => \App\Models\DynamicAppConfig::getValue('ads.banner_id_android', ''),
                'ios_unit_id' => \App\Models\DynamicAppConfig::getValue('ads.banner_id_ios', ''),
            ],
            'interstitial' => [
                'enabled' => filter_var(\App\Models\DynamicAppConfig::getValue('ads.interstitial_enabled', false), FILTER_VALIDATE_BOOLEAN),
                'android_unit_id' => \App\Models\DynamicAppConfig::getValue('ads.interstitial_id_android', ''),
                'ios_unit_id' => \App\Models\DynamicAppConfig::getValue('ads.interstitial_id_ios', ''),
                'frequency' => (int) \App\Models\DynamicAppConfig::getValue('ads.interstitial_frequency', 3),
            ],
            'rewarded' => [
                'enabled' => filter_var(\App\Models\DynamicAppConfig::getValue('ads.rewarded_enabled', false), FILTER_VALIDATE_BOOLEAN),
                'android_unit_id' => \App\Models\DynamicAppConfig::getValue('ads.rewarded_id_android', ''),
                'ios_unit_id' => \App\Models\DynamicAppConfig::getValue('ads.rewarded_id_ios', ''),
                'credits_per_watch' => (int) \App\Models\DynamicAppConfig::getValue('ads.rewarded_credits', 1),
            ],
        ];

        // Return complete configuration
        return response()->json([
            'appName' => \App\Models\FrontendConfig::getValue('mobile.app_name', 'BlinkStudy AI'),
            'appLogo' => $appLogo,
            'appIcon' => $appIcon,
            'splashScreen' => $splashScreen,
            'primaryColor' => \App\Models\FrontendConfig::getValue('mobile.primary_color', '#0D9488'),
            'secondaryColor' => \App\Models\FrontendConfig::getValue('mobile.secondary_color', '#F59E0B'),
            'accentColor' => \App\Models\FrontendConfig::getValue('mobile.accent_color', '#99F6E4'),
            'theme' => \App\Models\FrontendConfig::getValue('mobile.theme', 'light'),
            'fontFamily' => \App\Models\FrontendConfig::getValue('mobile.font_family', 'Outfit'),
            'features' => $features,
            'screens' => $screens,
            'forceUpdate' => $forceUpdate,
            'minVersion' => $minSupportedVersion,
            'latestVersion' => $latestVersion,
            'updateMessage' => $updateMessage,
            'updateUrlAndroid' => $updateUrlAndroid,
            'updateUrlIos' => $updateUrlIos,
            'app_version' => config('app.version', '1.0.0'),
            'credits' => $credits,
            'content' => $content,
            'ai' => $aiSettings,
            'auth' => $authConfig,
            'usage_limits' => $usageLimitConfig,
            'profile' => $profileConfig,
            'plans' => $plans,
            'trial_autopay' => [
                'enabled' => (bool) config('trial.enabled'),
                'price' => (int) config('trial.price'),
                'days' => (int) config('trial.days'),
                'renewal_price' => (int) config('trial.renewal_price'),
                'plan_slug' => config('trial.plan_slug'),
                'headline' => config('trial.offer.headline'),
                'subline' => config('trial.offer.subline'),
                'tag' => config('trial.offer.tag'),
                'ui' => config('trial.ui'),
            ],
            'exams' => $exams,
            'policies' => $policies,
            'ads' => $adsConfig,
        ]);
    } catch (\Exception $e) {
        // Fallback configuration when database is not available
        return response()->json([
            'appName' => 'BlinkStudy AI',
            'appLogo' => null,
            'appIcon' => null,
            'splashScreen' => null,
            'primaryColor' => '#0D9488',
            'secondaryColor' => '#F59E0B',
            'accentColor' => '#99F6E4',
            'theme' => 'light',
            'fontFamily' => 'Outfit',
            'features' => [
                'imageUpload' => true,
                'fileUpload' => true,
                'voiceInput' => true,
                'multipleModels' => true,
                'shareChat' => true,
                'pdfUpload' => true,
                'imageAnalysis' => true
            ],
            'screens' => [
                'chat' => true,
                'settings' => true,
                'profile' => true,
                'plans' => false,
                'notebook' => false
            ],
            'forceUpdate' => false,
            'minVersion' => '1.0.0',
            'app_version' => '1.0.0',
            'plans' => [],
            'policies' => [],
        ]);
    }
});

// Testimonials API (Public) - For mobile app
Route::get('/testimonials', function () {
    try {
        $testimonials = \App\Models\Testimonial::active()->ordered()->get();
        return response()->json([
            'success' => true,
            'testimonials' => $testimonials
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'testimonials' => []
        ]);
    }
});

// Quiz API - For mobile app (Authenticated)
Route::middleware('auth:sanctum')->prefix('quiz')->group(function () {
    // Get quiz history
    Route::get('/history', function (\Illuminate\Http\Request $request) {
        try {
            $validated = ApiValidator::validateQuery($request, [
                'limit' => ApiValidator::paginationLimit(50),
            ]);
            $user = $request->user();
            $limit = $validated['limit'] ?? 50;

            $history = \App\Models\QuizAttempt::where('user_id', $user->id)
                ->completed()
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function($attempt) {
                    return [
                        'id' => $attempt->id,
                        'topic' => $attempt->topic,
                        'title' => $attempt->title,
                        'exam' => $attempt->exam,
                        'subject' => $attempt->subject,
                        'difficulty_level' => $attempt->difficulty_level,
                        'score' => $attempt->score,
                        'total_questions' => $attempt->total_questions,
                        'correct_answers' => $attempt->correct_answers,
                        'wrong_answers' => $attempt->wrong_answers,
                        'skipped_questions' => $attempt->skipped_questions,
                        'time_taken_seconds' => $attempt->time_taken_seconds,
                        'time_taken' => $attempt->formatted_time_taken,
                        'created_at' => $attempt->created_at->toISOString(),
                        'date' => $attempt->created_at->format('Y-m-d'),
                        'formatted_date' => $attempt->created_at->format('M d, Y'),
                        'status' => $attempt->status,
                        'questions_data' => $attempt->questions_data,
                        'answers_data' => $attempt->answers_data,
                    ];
                });

            return response()->json([
                'success' => true,
                'history' => $history
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'history' => [],
                'error' => $e->getMessage()
            ], 500);
        }
    });

    // Get quiz statistics
    Route::get('/stats', function (\Illuminate\Http\Request $request) {
        try {
            $validated = ApiValidator::validateQuery($request, [
                'period' => 'nullable|string|in:today,weekly,monthly,all',
            ]);
            $user = $request->user();
            $period = $validated['period'] ?? 'weekly';

            // Calculate date range based on period
            $now = \Carbon\Carbon::now();
            $start = null;
            $end = null;

            switch ($period) {
                case 'today':
                    $start = $now->copy()->startOfDay();
                    $end = $now->copy()->endOfDay();
                    break;
                case 'monthly':
                    $start = $now->copy()->startOfMonth();
                    $end = $now->copy()->endOfMonth();
                    break;
                case 'all':
                    // No date filter for all-time stats
                    $start = null;
                    $end = null;
                    break;
                case 'weekly':
                default:
                    $start = $now->copy()->startOfWeek();
                    $end = $now->copy()->endOfWeek();
                    break;
            }

            // Get attempts in the period (or all if period=all)
            $attemptsQuery = \App\Models\QuizAttempt::where('user_id', $user->id)
                ->completed();

            if ($start && $end) {
                $attemptsQuery->dateRange($start, $end);
            }

            $attempts = $attemptsQuery->get();

            $totalQuizzes = $attempts->count();
            $averageScore = $totalQuizzes > 0 ? round($attempts->avg('score'), 0) : 0;
            $bestScore = $totalQuizzes > 0 ? round($attempts->max('score'), 0) : 0;
            $worstScore = $totalQuizzes > 0 ? round($attempts->min('score'), 0) : 0;

            // Calculate total questions, correct, and wrong answers
            $totalQuestions = $attempts->sum('total_questions');
            $totalCorrect = $attempts->sum('correct_answers');
            $totalWrong = $attempts->sum('wrong_answers');
            $totalSkipped = $attempts->sum('skipped_questions');

            // Calculate total time
            $totalSeconds = $attempts->sum('time_taken_seconds');
            $totalHours = floor($totalSeconds / 3600);
            $totalMinutes = floor(($totalSeconds % 3600) / 60);
            $totalTime = $totalHours > 0 ? "{$totalHours}h {$totalMinutes}m" : "{$totalMinutes}m";

            // Calculate average time per quiz
            $avgSeconds = $totalQuizzes > 0 ? $totalSeconds / $totalQuizzes : 0;
            $avgMinutes = floor($avgSeconds / 60);
            $avgSecs = $avgSeconds % 60;
            $avgTimePerQuiz = "{$avgMinutes}m {$avgSecs}s";

            // Calculate improvement (compare with previous period)
            $previousStart = null;
            $previousEnd = null;
            $previousAttempts = collect([]);

            if ($period !== 'all' && $start && $end) {
                switch ($period) {
                    case 'today':
                        $previousStart = $start->copy()->subDay();
                        $previousEnd = $end->copy()->subDay();
                        break;
                    case 'monthly':
                        $previousStart = $start->copy()->subMonth();
                        $previousEnd = $end->copy()->subMonth();
                        break;
                    case 'weekly':
                    default:
                        $previousStart = $start->copy()->subWeek();
                        $previousEnd = $end->copy()->subWeek();
                        break;
                }

                $previousAttempts = \App\Models\QuizAttempt::where('user_id', $user->id)
                    ->completed()
                    ->dateRange($previousStart, $previousEnd)
                    ->get();
            }

            $previousAverageScore = $previousAttempts->count() > 0 ? round($previousAttempts->avg('score'), 0) : 0;
            $improvement = $previousAverageScore > 0
                ? round((($averageScore - $previousAverageScore) / $previousAverageScore) * 100, 0)
                : 0;
            $improvementText = $improvement >= 0 ? "+{$improvement}%" : "{$improvement}%";

            // Calculate day streak (consecutive days with quizzes)
            $dayStreak = 0;
            $checkDate = $now->copy()->startOfDay();
            while (true) {
                $hasQuiz = \App\Models\QuizAttempt::where('user_id', $user->id)
                    ->completed()
                    ->whereDate('created_at', $checkDate)
                    ->exists();

                if (!$hasQuiz) {
                    break;
                }

                $dayStreak++;
                $checkDate->subDay();

                // Limit to prevent infinite loop
                if ($dayStreak > 365) {
                    break;
                }
            }

            // Format attempts for response
            $attemptsData = $attempts->map(function($attempt) {
                return [
                    'id' => $attempt->id,
                    'title' => $attempt->title,
                    'topic' => $attempt->topic,
                    'subject' => $attempt->subject,
                    'exam' => $attempt->exam,
                    'difficulty_level' => $attempt->difficulty_level,
                    'score' => $attempt->score,
                    'total_questions' => $attempt->total_questions,
                    'correct_answers' => $attempt->correct_answers,
                    'wrong_answers' => $attempt->wrong_answers,
                    'time_taken' => $attempt->formatted_time_taken,
                    'date' => $attempt->created_at->format('Y-m-d'),
                    'formatted_date' => $attempt->created_at->format('M d, Y'),
                    'time' => $attempt->created_at->format('h:i A'),
                ];
            });

            return response()->json([
                'success' => true,
                'stats' => [
                    'totalQuizzes' => $totalQuizzes,
                    'averageScore' => $averageScore,
                    'bestScore' => $bestScore,
                    'worstScore' => $worstScore,
                    'totalTime' => $totalTime,
                    'totalTimeSeconds' => $totalSeconds,
                    'avgTimePerQuiz' => $avgTimePerQuiz,
                    'improvement' => $improvementText,
                    'dayStreak' => $dayStreak,
                    'totalQuestions' => $totalQuestions,
                    'correctAnswers' => $totalCorrect,
                    'wrongAnswers' => $totalWrong,
                    'skippedQuestions' => $totalSkipped,
                ],
                'attempts' => $attemptsData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'stats' => [
                    'totalQuizzes' => 0,
                    'averageScore' => 0,
                    'bestScore' => 0,
                    'worstScore' => 0,
                    'totalTime' => '0m',
                    'totalTimeSeconds' => 0,
                    'avgTimePerQuiz' => '0m 0s',
                    'improvement' => '+0%',
                    'dayStreak' => 0,
                    'totalQuestions' => 0,
                    'correctAnswers' => 0,
                    'wrongAnswers' => 0,
                    'skippedQuestions' => 0,
                ],
                'attempts' => [],
                'error' => $e->getMessage()
            ], 500);
        }
    });

    // Save quiz attempt
    Route::post('/attempts', function (\Illuminate\Http\Request $request) {
        try {
            $user = $request->user();

            $validated = $request->validate([
                'title' => 'required|string',
                'exam' => 'nullable|string',
                'subject' => 'nullable|string',
                'topic' => 'nullable|string',
                'difficulty_level' => 'nullable|string', // Made nullable, will default to 'medium'
                'language' => 'nullable|string', // Made nullable, will default to 'english'
                'duration_minutes' => 'nullable|integer', // Made nullable, will calculate from time_taken
                'total_questions' => 'required|integer',
                'correct_answers' => 'required|integer',
                'wrong_answers' => 'required|integer',
                'skipped_questions' => 'required|integer',
                'score' => 'required|numeric',
                'time_taken_seconds' => 'required|integer',
                'status' => 'nullable|string', // Made nullable, will default to 'completed'
                'mobile_chat_id' => 'nullable|integer',
            ]);

            // Normalize difficulty level
            $difficultyLevel = strtolower($validated['difficulty_level'] ?? 'medium');
            if (!in_array($difficultyLevel, ['easy', 'medium', 'hard'])) {
                $difficultyLevel = 'medium'; // Default for 'mixed' or any other value
            }

            // Default status to 'completed' if not provided
            $status = strtolower($validated['status'] ?? 'completed');
            if (!in_array($status, ['in_progress', 'completed', 'abandoned'])) {
                $status = 'completed';
            }

            // SECURITY: Server-side validation of quiz answers
            $totalQuestions = (int)$validated['total_questions'];
            $correctAnswers = (int)$validated['correct_answers'];
            $wrongAnswers = (int)$validated['wrong_answers'];
            $skippedQuestions = (int)$validated['skipped_questions'];

            // Validate that answers add up correctly
            if ($correctAnswers + $wrongAnswers + $skippedQuestions !== $totalQuestions) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid quiz data: answer counts do not match total questions',
                ], 422);
            }

            // Validate no negative values
            if ($correctAnswers < 0 || $wrongAnswers < 0 || $skippedQuestions < 0 || $totalQuestions < 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid quiz data: negative values not allowed',
                ], 422);
            }

            // Validate time_taken_seconds is reasonable (max 24 hours)
            $timeTaken = max(0, min(86400, (int)$validated['time_taken_seconds']));

            // Server-calculated score (overrides client score)
            $calculatedScore = $totalQuestions > 0 ? round(($correctAnswers / $totalQuestions) * 100, 2) : 0;

            // Validate optional linked chat belongs to this user
            $mobileChatId = $validated['mobile_chat_id'] ?? null;
            if ($mobileChatId) {
                \App\Support\ResourceAuthorizer::ownedMobileChat($user, (int) $mobileChatId);
            }

            $attempt = \App\Models\QuizAttempt::create([
                'user_id' => $user->id,
                'mobile_chat_id' => $mobileChatId,
                'title' => $validated['title'],
                'exam' => $validated['exam'] ?? null,
                'subject' => $validated['subject'] ?? null,
                'topic' => $validated['topic'] ?? null,
                'difficulty_level' => $difficultyLevel,
                'language' => $validated['language'] ?? 'english',
                'duration_minutes' => $validated['duration_minutes'] ?? ceil($timeTaken / 60),
                'total_questions' => $totalQuestions,
                'correct_answers' => $correctAnswers,
                'wrong_answers' => $wrongAnswers,
                'skipped_questions' => $skippedQuestions,
                'score' => $calculatedScore, // Server-calculated score
                'time_taken_seconds' => $timeTaken,
                'status' => $status,
                'started_at' => \Carbon\Carbon::now()->subSeconds($timeTaken),
                'completed_at' => \Carbon\Carbon::now(),
            ]);

            if (!empty($validated['topic'])) {
                \App\Services\LearningAnalyticsService::trackPerformance(
                    $user->id,
                    $validated['topic'],
                    $validated['subject'] ?? 'General',
                    $calculatedScore >= 60,
                    $timeTaken
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Quiz attempt saved successfully',
                'attempt' => [
                    'id' => $attempt->id,
                    'score' => $attempt->score,
                    'correct_answers' => $attempt->correct_answers,
                    'total_questions' => $attempt->total_questions,
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save quiz attempt',
                'error' => $e->getMessage()
            ], 500);
        }
    });
});

// Pages API (Public) - For mobile app and website
Route::prefix('pages')->group(function () {
    // Get all active pages for app
    Route::get('/', function () {
        $pages = \App\Models\Page::active()
            ->inApp()
            ->ordered()
            ->get(['id', 'title', 'slug', 'content', 'order']);

        return response()->json([
            'success' => true,
            'pages' => $pages,
        ]);
    });

    // Get single page by slug
    Route::get('/{slug}', function (\Illuminate\Http\Request $request, $slug) {
        ApiValidator::validateRoute($request, ['slug' => $slug], [
            'slug' => ApiValidator::slug(),
        ]);

        $page = \App\Models\Page::where('slug', $slug)
            ->active()
            ->first();

        if (!$page) {
            return response()->json([
                'success' => false,
                'message' => 'Page not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'page' => [
                'id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
                'content' => $page->content,
                'meta_title' => $page->meta_title,
                'meta_description' => $page->meta_description,
            ],
        ]);
    });
});

// ========================================
// ADMIN APP CONFIG ROUTES (Mobile Admin)
// ========================================
Route::middleware(['auth:sanctum', 'admin.only'])->prefix('admin')->group(function () {
    // Get app config for admin
    Route::get('/app-config', function () {
        \App\Support\ResourceAuthorizer::ensureAdmin(auth()->user());

        return response()->json([
            'success' => true,
            'config' => [
                'force_update' => (bool) \App\Models\FrontendConfig::getValue('mobile.force_update', false),
                'min_version' => \App\Models\FrontendConfig::getValue('mobile.min_version', '1.0.0'),
                'update_message' => \App\Models\FrontendConfig::getValue('mobile.update_message', 'A new version is available. Please update to continue.'),
            ]
        ]);
    });

    // Update force update configuration
    Route::put('/app-config/force-update', function (\Illuminate\Http\Request $request) {
        \App\Support\ResourceAuthorizer::ensureAdmin($request->user());

        $validated = $request->validate([
            'force_update' => 'required|boolean',
            'min_version' => 'required|string|regex:/^\d+\.\d+\.\d+$/',
            'update_message' => 'required|string|max:500',
        ]);

        \App\Models\FrontendConfig::setValue('mobile.force_update', $validated['force_update'], 'boolean');
        \App\Models\FrontendConfig::setValue('mobile.min_version', $validated['min_version'], 'string');
        \App\Models\FrontendConfig::setValue('mobile.update_message', $validated['update_message'], 'string');

        return response()->json([
            'success' => true,
            'message' => 'Force update configuration updated successfully'
        ]);
    });
});

// ========================================
// ₹1 TRIAL + UPI AUTOPAY (Textbook-style)
// ========================================
Route::middleware('auth:sanctum')->prefix('trial')->group(function () {
    Route::get('/offer', [\App\Http\Controllers\Api\TrialController::class, 'offer']);
    Route::get('/status', [\App\Http\Controllers\Api\TrialController::class, 'status']);
    Route::post('/start', [\App\Http\Controllers\Api\TrialController::class, 'start']);
    Route::post('/verify', [\App\Http\Controllers\Api\TrialController::class, 'verify']);
    Route::post('/cancel', [\App\Http\Controllers\Api\TrialController::class, 'cancel']);
});

// ========================================
// PRICING & SUBSCRIPTION ROUTES
// ========================================

// Get all pricing plans (public)
Route::get('/plans', function () {
    try {
        $plans = \App\Models\UserPlan::where('is_active', true)
            ->where('slug', '!=', 'free')
            ->where('price', '>', 0)
            ->orderBy('order')
            ->orderBy('id')
            ->get()
            ->map(function ($plan) {
                // Decode features JSON (handle double-encoding)
                $features = $plan->features;
                if (is_string($features)) {
                    $features = json_decode($features, true) ?? [];
                }
                if (is_string($features)) {
                    $features = json_decode($features, true) ?? [];
                }
                if (!is_array($features)) {
                    $features = [];
                }

                $dailyLimits = $features['daily_limits'] ?? [];
                $featuresList = $features['features_list'] ?? $features['features'] ?? [];

                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'slug' => $plan->slug,
                    'description' => $plan->description ?? $plan->billing_description,
                    'price' => (float) ($plan->price ?? 0),
                    'billing_period' => $plan->billing_period ?? 'month',
                    'validity_days' => (int) $plan->validity_days,
                    'is_active' => (bool) $plan->is_active,
                    'features' => $featuresList,
                    'daily_limits' => $dailyLimits,
                    'max_video_length_seconds' => (int) ($features['max_video_length_seconds'] ?? 30),
                    'frames_per_video' => (int) ($features['frames_per_video'] ?? 5),
                    'pages_per_pdf' => (int) ($features['pages_per_pdf'] ?? 5),
                    'history_days' => (int) ($features['history_days'] ?? 3),
                    'watermark' => (bool) ($features['watermark'] ?? true),
                    'ads' => (bool) ($features['ads'] ?? true),
                    'priority_queue' => (bool) ($features['priority_queue'] ?? false),
                ];
            });

        return response()->json([
            'success' => true,
            'plans' => $plans,
        ]);
    } catch (\Exception $e) {
        \Log::error('Plans API error: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Failed to load plans',
            'plans' => [],
        ]);
    }
});

// Get enabled payment gateways
Route::get('/payment-gateways', function () {
    $gateways = \App\Models\PaymentGateway::where('is_enabled', true)
        ->orderBy('sort_order')
        ->get()
        ->map(function ($gateway) {
            return [
                'name' => $gateway->name,
                'display_name' => $gateway->display_name,
                'is_enabled' => true,
            ];
        });

    return response()->json([
        'success' => true,
        'gateways' => $gateways,
    ]);
});

// Get user's current subscription
Route::middleware('auth:sanctum')->get('/subscription', function (Request $request) {
    try {
        $user = $request->user();

        $subscription = DB::table('user_subscriptions')
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->where('end_date', '>=', now())
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$subscription) {
            return response()->json([
                'success' => true,
                'subscription' => null,
                'plan' => null,
            ]);
        }

        $plan = DB::table('user_plans')->find($subscription->plan_id);

        return response()->json([
            'success' => true,
            'subscription' => [
                'id' => $subscription->id,
                'status' => $subscription->status,
                'billingCycle' => $subscription->billing_cycle,
                'amountPaid' => (float) $subscription->amount_paid,
                'startDate' => $subscription->start_date,
                'endDate' => $subscription->end_date,
                'nextBillingDate' => $subscription->next_billing_date,
                'autoRenew' => (bool) $subscription->auto_renew,
            ],
            'plan' => $plan ? [
                'id' => $plan->id,
                'name' => $plan->name,
                'slug' => $plan->slug,
            ] : null,
        ]);
    } catch (\Exception $e) {
        // Fallback if subscription tables not available
        return response()->json([
            'success' => true,
            'subscription' => null,
            'plan' => null,
        ]);
    }
});

// Initiate subscription/upgrade
Route::middleware('auth:sanctum')->post('/subscribe', function (Request $request) {
    $validated = $request->validate([
        'plan_id' => 'required|exists:user_plans,id',
        'billing_cycle' => 'nullable|in:monthly,yearly',
        'payment_method' => 'required|string',
    ]);

    // Default billing cycle to monthly if not provided
    $billingCycle = $validated['billing_cycle'] ?? 'monthly';

    $user = $request->user();
    $plan = DB::table('user_plans')->find($validated['plan_id']);

    if (!$plan || (float) $plan->price <= 0 || $plan->slug === 'free') {
        return response()->json([
            'success' => false,
            'message' => 'Invalid plan. Please choose a paid subscription.',
        ], 422);
    }

    // Calculate amount based on billing cycle
    $amount = $billingCycle === 'yearly'
        ? $plan->yearly_price
        : $plan->price;

    // For paid plans, create pending subscription
    $pendingEndDate = $billingCycle === 'yearly' ? now()->addYear() : now()->addMonth();
    $subscriptionId = DB::table('user_subscriptions')->insertGetId([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'status' => 'pending',
        'billing_cycle' => $billingCycle,
        'amount_paid' => $amount,
        'start_date' => now(),
        'end_date' => $pendingEndDate,
        'payment_method' => $validated['payment_method'],
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Subscription created. Please complete payment.',
        'requiresPayment' => true,
        'subscriptionId' => $subscriptionId,
        'amount' => $amount,
        'currency' => 'USD',
    ]);
});

// Cancel subscription
Route::middleware('auth:sanctum')->post('/subscription/cancel', function (Request $request) {
    $user = $request->user();

    $updated = DB::table('user_subscriptions')
        ->where('user_id', $user->id)
        ->where('status', 'active')
        ->update([
            'auto_renew' => false,
            'cancelled_at' => now(),
            'updated_at' => now(),
        ]);

    return response()->json([
        'success' => $updated > 0,
        'message' => $updated > 0
            ? 'Subscription will end at the current billing period'
            : 'No active subscription found',
    ]);
});

// Get enabled payment gateways
Route::middleware('auth:sanctum')->get('/payment/gateways', function (Request $request) {
    $gateways = \App\Models\PaymentGateway::where('is_enabled', true)
        ->where('is_configured', true)
        ->orderBy('sort_order')
        ->get()
        ->map(function ($gateway) {
            return [
                'name' => $gateway->name,
                'display_name' => $gateway->display_name,
                'is_test_mode' => $gateway->is_test_mode,
            ];
        });

    return response()->json([
        'success' => true,
        'gateways' => $gateways,
    ]);
});

// Create payment order
Route::middleware('auth:sanctum')->post('/subscription/create-order', function (Request $request) {
    $validated = $request->validate([
        'plan_id' => 'required|exists:user_plans,id',
        'billing_cycle' => 'nullable|in:monthly,yearly',
        'gateway' => 'required|string|in:razorpay,cashfree,phonepe',
    ]);

    // Default billing cycle to monthly if not provided
    $billingCycle = $validated['billing_cycle'] ?? 'monthly';

    $user = $request->user();
    $plan = DB::table('user_plans')->find($validated['plan_id']);

    // Calculate amount
    $amount = $billingCycle === 'yearly'
        ? $plan->yearly_price
        : $plan->price;

    // Get payment gateway
    $gateway = \App\Models\PaymentGateway::where('name', $validated['gateway'])
        ->where('is_enabled', true)
        ->first();

    if (!$gateway || !$gateway->isConfigured()) {
        return response()->json([
            'success' => false,
            'message' => 'Payment gateway not available or not configured.',
        ], 400);
    }

    // Use PaymentService to create order
    $paymentService = app(\App\Services\PaymentService::class);
    $result = $paymentService->createOrder(
        $validated['gateway'],
        $amount,
        'subscription',
        $user->id,
        [
            'plan_id' => $plan->id,
            'plan_name' => $plan->name,
            'billing_cycle' => $billingCycle,
        ]
    );

    if (!$result['success']) {
        return response()->json([
            'success' => false,
            'message' => $result['message'],
        ], 400);
    }

    // Store order details for verification
    DB::table('payment_orders')->insert([
        'order_id' => $result['gateway_order_id'],
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'amount' => $amount,
        'currency' => 'INR',
        'billing_cycle' => $billingCycle,
        'payment_gateway' => $validated['gateway'],
        'status' => 'pending',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return response()->json([
        'success' => true,
        'order' => [
            'id' => $result['gateway_order_id'],
            'transaction_id' => $result['transaction_id'],
            'amount' => $amount,
            'currency' => 'INR',
            'plan_name' => $plan->name,
            'gateway_data' => $result['gateway_data'],
        ],
    ]);
});

// Verify payment and activate subscription
Route::middleware('auth:sanctum')->post('/subscription/verify-payment', function (Request $request) {
    $validated = $request->validate([
        'order_id' => 'required|string',
        'payment_id' => 'required|string',
        'signature' => 'nullable|string',
    ]);

    $user = $request->user();

    $order = \App\Support\ResourceAuthorizer::ownedPaymentOrder($user, $validated['order_id']);

    // SECURITY: Idempotency check - prevent replay attacks
    if ($order->status === 'completed') {
        // Already processed - return success without reprocessing
        $plan = DB::table('user_plans')->find($order->plan_id);
        return response()->json([
            'success' => true,
            'message' => 'Payment already verified',
            'subscription' => [
                'plan_name' => $plan->name ?? 'Unknown',
                'already_processed' => true,
            ],
        ]);
    }

    // Verify payment using PaymentService
    $paymentService = app(\App\Services\PaymentService::class);

    // Prepare payment data based on gateway
    $paymentData = ['payment_id' => $validated['payment_id']];

    // Add signature for Razorpay
    if ($order->payment_gateway === 'razorpay' && $validated['signature']) {
        $paymentData['razorpay_payment_id'] = $validated['payment_id'];
        $paymentData['razorpay_signature'] = $validated['signature'];
    }

    // Get transaction record by gateway_order_id (must belong to authenticated user)
    $transaction = \App\Support\ResourceAuthorizer::ownedGatewayTransaction($user, $order->order_id);

    $result = $paymentService->verifyPayment($transaction->transaction_id, $paymentData);

    if (!$result['success']) {
        return response()->json([
            'success' => false,
            'message' => $result['message'],
        ], 400);
    }

    // Get plan details from user_plans table
    $plan = DB::table('user_plans')->find($order->plan_id);

    // Update order status
    DB::table('payment_orders')
        ->where('order_id', $validated['order_id'])
        ->update([
            'status' => 'completed',
            'payment_id' => $validated['payment_id'],
            'updated_at' => now(),
        ]);

    // Calculate subscription dates
    $endDate = $order->billing_cycle === 'yearly'
        ? now()->addYear()
        : now()->addMonth();

    // Create or update subscription
    DB::table('user_subscriptions')->updateOrInsert(
        ['user_id' => $user->id],
        [
            'plan_id' => $plan->id,
            'status' => 'active',
            'billing_cycle' => $order->billing_cycle,
            'amount_paid' => $order->amount,
            'start_date' => now(),
            'end_date' => $endDate,
            'next_billing_date' => $endDate,
            'payment_method' => $order->payment_gateway,
            'transaction_id' => $validated['payment_id'],
            'auto_renew' => true,
            'updated_at' => now(),
        ]
    );

    // Update user's plan and expiry date
    DB::table('users')->where('id', $user->id)->update([
        'plan_id' => $plan->id,
        'plan_expires_at' => $endDate,
    ]);

    // No credits to grant — usage is tracked via daily/monthly counters

    return response()->json([
        'success' => true,
        'message' => 'Payment verified and subscription activated!',
        'subscription' => [
            'plan_name' => $plan->name,
            'start_date' => now()->toDateString(),
            'end_date' => $endDate->toDateString(),
        ],
    ]);
});

// ========================================
// CASHFREE PAYMENT ROUTES
// ========================================

Route::middleware('auth:sanctum')->group(function () {
    // Create Cashfree payment order
    Route::post('/payment/cashfree/create', [\App\Http\Controllers\PaymentController::class, 'createCashfreeOrder']);

    // Get payment plans
    Route::get('/payment/plans', function () {
        $plans = \App\Models\PricingPlan::where('is_active', true)
            ->orderBy('price')
            ->get();

        return response()->json([
            'success' => true,
            'plans' => $plans,
        ]);
    });

    // ========================================
    // MULTI-GATEWAY PAYMENT ROUTES
    // ========================================

    // Get enabled payment gateways
    Route::get('/payment/gateways', [\App\Http\Controllers\PaymentController::class, 'getEnabledGateways']);

    // Create payment order for any gateway
    Route::post('/payment/create-order', [\App\Http\Controllers\PaymentController::class, 'createPaymentOrder']);

    // Verify payment
    Route::post('/payment/verify', [\App\Http\Controllers\PaymentController::class, 'verifyPaymentOrder']);
});

// Web payment routes (web callback - no auth required for Cashfree)
Route::get('/payment/cashfree/callback', [\App\Http\Controllers\PaymentController::class, 'cashfreeCallback'])->name('payment.cashfree.callback');

// Gateway-specific callbacks (web - no auth required)
Route::get('/payment/callback/{gateway}', [\App\Http\Controllers\PaymentController::class, 'gatewayCallback']);
Route::post('/payment/callback/{gateway}', [\App\Http\Controllers\PaymentController::class, 'gatewayCallback']);

// BlinkStudy AI Routes (authenticated + AI rate limit: 10/min per user)
Route::middleware(['auth:sanctum', 'throttle:ai'])->prefix('blinkstudy')->group(function () {

    // Main chat endpoint
    Route::post('/chat', [AIChatController::class, 'chat']);

    // Subject-specific endpoints
    Route::post('/math', [AIChatController::class, 'handleMathQuestion']);
    Route::post('/science', [AIChatController::class, 'handleScienceQuestion']);

    // Content generation endpoints
    Route::post('/mcqs', [AIChatController::class, 'generateMCQs']);
    Route::post('/notes', [AIChatController::class, 'generateNotes']);

    // Image/PDF processing
    Route::post('/solve-image', [AIChatController::class, 'solveFromImage'])
        ->middleware('throttle:upload');
});

// ============================================================
// DYNAMIC APP CONFIGURATION (Remote Config)
// ============================================================
// Get app configs and check for updates (public endpoint)
Route::get('/app/configs', [\App\Http\Controllers\Api\DynamicAppController::class, 'getConfigs']);
Route::get('/app/icon', [\App\Http\Controllers\Api\DynamicAppController::class, 'getIconInfo']);

// Get feature flags
Route::middleware('auth:sanctum')->get('/app/features', [\App\Http\Controllers\Api\DynamicAppController::class, 'getFeatureFlags']);

// Admin routes for managing app configs (auth required)
Route::middleware(['auth:sanctum', 'admin.only'])->prefix('admin/app')->group(function () {
    Route::get('/configs', [\App\Http\Controllers\Api\DynamicAppController::class, 'getAllConfigs']);
    Route::post('/configs', [\App\Http\Controllers\Api\DynamicAppController::class, 'updateConfigs']);
    Route::post('/icon/upload', [\App\Http\Controllers\Api\DynamicAppController::class, 'uploadIcon'])
        ->middleware('throttle:upload');
    Route::post('/trigger-update', [\App\Http\Controllers\Api\DynamicAppController::class, 'triggerUpdate']);
    Route::post('/test-notification', [\App\Http\Controllers\Api\DynamicAppController::class, 'sendTestNotification']);
});

// ============================================================
// PERSONALIZED REVISION
// ============================================================
Route::middleware('auth:sanctum')->prefix('revision')->group(function () {
    Route::get('/profile', [\App\Http\Controllers\Api\RevisionController::class, 'profile']);
    Route::get('/plan', [\App\Http\Controllers\Api\RevisionController::class, 'plan']);
    Route::get('/flashcards', [\App\Http\Controllers\Api\RevisionController::class, 'flashcards']);
});

// ============================================================
// EXAM PREP SYSTEM
// ============================================================
Route::middleware('auth:sanctum')->prefix('exams')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\ExamController::class, 'index']);
    Route::get('/{exam}', [\App\Http\Controllers\Api\ExamController::class, 'show']);
    Route::get('/{exam}/questions', [\App\Http\Controllers\Api\ExamController::class, 'getQuestions']);
    Route::post('/{exam}/generate-questions', [\App\Http\Controllers\Api\ExamController::class, 'generateQuestions'])
        ->middleware(['check.feature:exam_prep', 'throttle:ai']);

    // PYQ (Previous Year Questions) Routes
    Route::get('/{exam}/pyq-years', [\App\Http\Controllers\Api\ExamController::class, 'getAvailablePYQYears']);
    Route::get('/{exam}/pyq/{year}', [\App\Http\Controllers\Api\ExamController::class, 'getPYQPaper']);
    Route::post('/pyq-mock-test/generate', [\App\Http\Controllers\Api\ExamController::class, 'generatePYQMockTest'])
        ->middleware(['check.feature:exam_prep', 'throttle:ai']);

    // Mock Test Routes
    Route::post('/mock-test/generate', [\App\Http\Controllers\Api\ExamController::class, 'generateMockTest'])
        ->middleware(['check.feature:exam_prep', 'throttle:ai']);
    Route::post('/mock-test/{mockTest}/start', [\App\Http\Controllers\Api\ExamController::class, 'startMockTest']);
    Route::post('/mock-test/{mockTest}/submit', [\App\Http\Controllers\Api\ExamController::class, 'submitMockTest']);
    Route::get('/mock-test/{mockTest}/result', [\App\Http\Controllers\Api\ExamController::class, 'getMockTestResult']);
    Route::get('/mock-test/history', [\App\Http\Controllers\Api\ExamController::class, 'getMockTestHistory']);
    Route::get('/{exam}/subject-analysis', [\App\Http\Controllers\Api\ExamController::class, 'getSubjectAnalysis']);
});

// ============================================================
// DAILY CHALLENGE
// ============================================================
Route::middleware('auth:sanctum')->prefix('daily-challenge')->group(function () {
    Route::get('/today', [\App\Http\Controllers\Api\DailyChallengeController::class, 'getToday']);
    Route::post('/start', [\App\Http\Controllers\Api\DailyChallengeController::class, 'start']);
    Route::post('/submit', [\App\Http\Controllers\Api\DailyChallengeController::class, 'submit']);
    Route::get('/leaderboard', [\App\Http\Controllers\Api\DailyChallengeController::class, 'leaderboard']);
    Route::get('/history', [\App\Http\Controllers\Api\DailyChallengeController::class, 'history']);
});

// ============================================================
// DAILY CHALLENGE
// ============================================================
Route::middleware(['auth:sanctum', 'admin.only'])->get('/usage/stats', function (Request $request) {
    \App\Support\ResourceAuthorizer::ensureAdmin($request->user());

    try {
        $validated = ApiValidator::validateQuery($request, [
            'days' => ['nullable', 'integer', 'min:1', 'max:' . config('api-validation.limits.stats_days_max', 365)],
        ]);
        $days = $validated['days'] ?? 7;

        // Get overall stats
        $overallStats = \App\Models\AiUsageTracking::where('created_at', '>=', now()->subDays($days))
            ->selectRaw('
                COUNT(*) as total_requests,
                SUM(total_tokens) as total_tokens,
                SUM(estimated_cost) as total_cost
            ')
            ->first();

        // Get feature breakdown
        $featureStats = [];
        foreach (['chat', 'quiz'] as $feature) {
            $stats = \App\Models\AiUsageTracking::getFeatureStats($feature, $days);
            $featureStats[$feature] = [
                'requests' => $stats['total_requests'] ?? 0,
                'tokens' => $stats['total_tokens'] ?? 0,
                'cost' => $stats['total_cost'] ?? 0,
            ];
        }

        // Get today's stats
        $todayStats = \App\Models\AiUsageTracking::whereDate('created_at', now())
            ->selectRaw('
                COUNT(*) as requests,
                SUM(total_tokens) as tokens,
                SUM(estimated_cost) as cost
            ')
            ->first();

        return response()->json([
            'success' => true,
            'period' => "{$days} days",
            'overall' => [
                'total_requests' => $overallStats->total_requests ?? 0,
                'total_tokens' => $overallStats->total_tokens ?? 0,
                'total_cost' => number_format($overallStats->total_cost ?? 0, 2),
            ],
            'today' => [
                'requests' => $todayStats->requests ?? 0,
                'tokens' => $todayStats->tokens ?? 0,
                'cost' => number_format($todayStats->cost ?? 0, 2),
            ],
            'features' => $featureStats,
            'timestamp' => now()->toIso8601String(),
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => 'Failed to fetch usage stats',
        ], 500);
    }
});

// ============================================================
// TOPPER CONNECT SYSTEM (PRD: Community)
// ============================================================
Route::middleware('auth:sanctum')->prefix('topper-connect')->group(function () {
    // Get available toppers
    Route::get('/toppers', [\App\Http\Controllers\Api\TopperConnectController::class, 'getToppers']);
    Route::get('/toppers/featured', [\App\Http\Controllers\Api\TopperConnectController::class, 'getFeaturedToppers']);
    Route::get('/toppers/{topperId}', [\App\Http\Controllers\Api\TopperConnectController::class, 'getTopperProfile']);

    // Chat requests (student)
    Route::post('/requests', [\App\Http\Controllers\Api\TopperConnectController::class, 'createRequest']);
    Route::get('/requests', [\App\Http\Controllers\Api\TopperConnectController::class, 'getMyRequests']);
    Route::delete('/requests/{requestId}', [\App\Http\Controllers\Api\TopperConnectController::class, 'cancelRequest']);

    // Conversations (student)
    Route::get('/conversations', [\App\Http\Controllers\Api\TopperConnectController::class, 'getMyConversations']);

    // Home data
    Route::get('/home', [\App\Http\Controllers\Api\TopperConnectController::class, 'getHomeData']);

    // Public doubts
    Route::get('/doubts', [\App\Http\Controllers\Api\TopperConnectController::class, 'getPublicDoubts']);
    Route::get('/doubts/search', [\App\Http\Controllers\Api\TopperConnectController::class, 'searchDoubts']);
    Route::get('/doubts/{doubtId}', [\App\Http\Controllers\Api\TopperConnectController::class, 'getPublicDoubt']);
    Route::post('/doubts/{doubtId}/upvote', [\App\Http\Controllers\Api\TopperConnectController::class, 'upvoteDoubt']);
});

// ============================================================
// TOPPER DASHBOARD (For Toppers)
// ============================================================
Route::middleware('auth:sanctum')->prefix('topper-dashboard')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\TopperDashboardController::class, 'getDashboard']);
    Route::get('/requests', [\App\Http\Controllers\Api\TopperDashboardController::class, 'getPendingRequests']);
    Route::post('/requests/{requestId}/accept', [\App\Http\Controllers\Api\TopperDashboardController::class, 'acceptRequest']);
    Route::post('/requests/{requestId}/reject', [\App\Http\Controllers\Api\TopperDashboardController::class, 'rejectRequest']);
    Route::post('/availability', [\App\Http\Controllers\Api\TopperDashboardController::class, 'updateAvailability']);
    Route::get('/conversations', [\App\Http\Controllers\Api\TopperDashboardController::class, 'getConversations']);
    Route::get('/earnings', [\App\Http\Controllers\Api\TopperDashboardController::class, 'getEarnings']);
    Route::get('/wallet', [\App\Http\Controllers\Api\TopperDashboardController::class, 'getWallet']);
    Route::put('/wallet/payout-details', [\App\Http\Controllers\Api\TopperDashboardController::class, 'updatePayoutDetails']);
    Route::post('/withdrawals', [\App\Http\Controllers\Api\TopperDashboardController::class, 'requestWithdrawal']);
    Route::get('/withdrawals', [\App\Http\Controllers\Api\TopperDashboardController::class, 'getWithdrawals']);
    Route::put('/profile', [\App\Http\Controllers\Api\TopperDashboardController::class, 'updateProfile']);
});

// ============================================================
// PAYMENT WEBHOOKS (No auth - server-to-server callbacks)
// ============================================================
Route::prefix('webhooks')->group(function () {
    Route::post('/razorpay', [\App\Http\Controllers\WebhookController::class, 'razorpay']);
});

// ============================================================
// STUDY BATTLES (PRD: Gamified Learning)
// ============================================================
Route::middleware('auth:sanctum')->prefix('study-battle')->group(function () {
    Route::post('/create', [\App\Http\Controllers\Api\StudyBattleController::class, 'create']);
    Route::post('/join/{code}', [\App\Http\Controllers\Api\StudyBattleController::class, 'join']);
    Route::post('/leave', [\App\Http\Controllers\Api\StudyBattleController::class, 'leave']);
    Route::post('/ready', [\App\Http\Controllers\Api\StudyBattleController::class, 'ready']);
    Route::post('/start', [\App\Http\Controllers\Api\StudyBattleController::class, 'start']);
    Route::get('/poll/{roomId}', [\App\Http\Controllers\Api\StudyBattleController::class, 'poll']);
    Route::post('/answer', [\App\Http\Controllers\Api\StudyBattleController::class, 'answer']);
    Route::get('/results/{roomId}', [\App\Http\Controllers\Api\StudyBattleController::class, 'results']);
    Route::get('/history', [\App\Http\Controllers\Api\StudyBattleController::class, 'history']);
    Route::get('/leaderboard', [\App\Http\Controllers\Api\StudyBattleController::class, 'leaderboard']);
    Route::get('/rooms', [\App\Http\Controllers\Api\StudyBattleController::class, 'rooms']);
});
