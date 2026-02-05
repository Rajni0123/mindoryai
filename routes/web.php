<?php

use App\Http\Controllers\AccessController;
use App\Http\Controllers\AiController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ImageAnalysisController;
use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Main Website
|--------------------------------------------------------------------------
|
| Main website routes for landing, login, register
| Chat interface: chat.yourdomain.com
| Admin panel: admin.yourdomain.com
| API: api.yourdomain.com
|
*/

// Main routes - Public website
// Production URL: yourdomain.com

// Landing page
Route::get('/', [PageController::class, 'landing'])->name('home');

// ========================================
// UNIFIED LOGIN SYSTEM (Single URL for Admin & Users)
// ========================================
// IMPORTANT: Only ONE login URL - role detection is automatic
Route::get('/login', [\App\Http\Controllers\Auth\UnifiedLoginController::class, 'showLoginForm'])->name('login');
Route::post('/login/send-otp', [\App\Http\Controllers\Auth\UnifiedLoginController::class, 'sendOTP'])->name('otp.send');
Route::post('/login/verify-otp', [\App\Http\Controllers\Auth\UnifiedLoginController::class, 'verifyOTP'])->name('otp.verify');
Route::post('/logout', [\App\Http\Controllers\Auth\UnifiedLoginController::class, 'logout'])->name('logout');

// Admin 2FA (After OTP verification, only for admin with 2FA enabled)
Route::get('/admin/verify-2fa', [\App\Http\Controllers\Auth\Admin2FAController::class, 'showVerify'])->name('admin.2fa.verify');
Route::post('/admin/verify-2fa', [\App\Http\Controllers\Auth\Admin2FAController::class, 'verify2FA'])->name('admin.2fa.verify.submit');

// Registration Routes - Disabled (Redirect to login)
Route::get('/register', function () {
    return redirect()->route('login');
})->name('register');
Route::post('/register', function () {
    return redirect()->route('login');
})->name('register.submit');

// Google OAuth Routes (Optional, if enabled)
Route::get('/auth/google/redirect', [\App\Http\Controllers\Auth\SocialAuthController::class, 'redirectToGoogle'])->name('google.redirect');
Route::get('/auth/google/callback', [\App\Http\Controllers\Auth\SocialAuthController::class, 'handleGoogleCallback'])->name('google.callback');

// ========================================
// LEGACY ROUTES - Redirect to unified login
// ========================================
// Old admin login URL → redirect to /login
Route::get('/secure-admin/access', function () {
    return redirect()->route('login');
});
Route::get('/admin/login', function () {
    return redirect()->route('login');
})->name('admin.login');
// Old user OTP login → redirect to /login
Route::get('/auth/otp', function () {
    return redirect()->route('login');
});

// Profile Setup Routes (for authenticated users)
Route::middleware('auth')->group(function () {
    Route::get('/profile/setup', function () {
        $user = auth()->user();

        // Redirect if profile already complete
        if ($user->name && $user->name !== 'User' && !str_starts_with($user->name, 'User ')) {
            return redirect()->route('dashboard');
        }

        return view('auth.profile-setup', compact('user'));
    })->name('profile.setup');

    Route::post('/profile/setup', function (Illuminate\Http\Request $request) {
        $validated = $request->validate([
            'name' => 'required|string|min:2|max:50',
            'mobile' => 'nullable|digits:10|unique:users,mobile,' . auth()->id(),
        ]);

        $user = auth()->user();
        $user->name = $validated['name'];

        if (isset($validated['mobile']) && $validated['mobile']) {
            $user->mobile = $validated['mobile'];
        }

        $user->save();

        return redirect()->route('dashboard')->with('success', 'Profile updated successfully!');
    })->name('profile.setup.update');
});

// Class Selection Routes (Only for regular users, not admins)
Route::middleware(['auth', 'user.role'])->group(function () {
    Route::get('/select-class', [\App\Http\Controllers\Auth\UnifiedLoginController::class, 'showClassSelection'])->name('class.select');
    Route::post('/update-class', [\App\Http\Controllers\Auth\UnifiedLoginController::class, 'updateClass'])->name('class.update');
});

// Apply IP whitelist middleware to all routes
Route::middleware(['ip.whitelist'])->group(function () {

    // Landing page (public) - already defined above
    // Route::get('/', [PageController::class, 'landing'])->name('home');

    // Static pages (public)
    Route::get('/plans', [PageController::class, 'plans'])->name('plans');

// Dynamic pages (CMS)
Route::get('/page/{slug}', [PageController::class, 'show'])->name('page.show');

// Dynamic Policies (database-driven)
Route::prefix('policies')->name('policy.')->group(function () {
    Route::get('/', [\App\Http\Controllers\PolicyDisplayController::class, 'index'])->name('index');
    Route::get('/{key}', [\App\Http\Controllers\PolicyDisplayController::class, 'show'])->name('show');
});

// Legacy redirects (backward compatibility) - Now redirect to dynamic policies
Route::get('/about', [\App\Http\Controllers\PolicyDisplayController::class, 'about'])->name('about');
Route::get('/privacy', [\App\Http\Controllers\PolicyDisplayController::class, 'privacy'])->name('privacy');
Route::get('/terms', [\App\Http\Controllers\PolicyDisplayController::class, 'terms'])->name('terms');
Route::get('/refund-policy', [\App\Http\Controllers\PolicyDisplayController::class, 'refund'])->name('refund');
Route::get('/cancellation-policy', [\App\Http\Controllers\PolicyDisplayController::class, 'cancellation'])->name('cancellation');
Route::get('/cookie-policy', [\App\Http\Controllers\PolicyDisplayController::class, 'cookie'])->name('cookies');
Route::get('/data-deletion', [\App\Http\Controllers\PolicyDisplayController::class, 'dataDeletion'])->name('data.deletion');
Route::get('/account-deletion', function() { return redirect()->route('data.deletion'); });
Route::get('/support', [\App\Http\Controllers\ContactController::class, 'show'])->name('support');
Route::post('/support/contact', [\App\Http\Controllers\ContactController::class, 'store'])->name('contact.store');
Route::get('/faq', function () { return view('pages.faq'); })->name('faq');

// Legacy contact redirect
Route::get('/contact', function() {
    return redirect()->route('support');
})->name('contact');

    // Access code verification (public - legacy, keeping for compatibility)
    Route::get('/verify-access', [AccessController::class, 'showVerification'])->name('verify.access');
    Route::post('/verify-access', [AccessController::class, 'verifyCode'])->name('verify.code');

    // Public API - Frontend Configuration (for both web and mobile app)
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/frontend-config', [\App\Http\Controllers\Api\FrontendConfigController::class, 'index'])->name('frontend-config');
        Route::get('/frontend-config/{key}', [\App\Http\Controllers\Api\FrontendConfigController::class, 'show'])->name('frontend-config.show');
    });

    // Protected routes (require authentication)
    Route::middleware(['auth'])->group(function () {

        // User Settings
        Route::get('/settings', [\App\Http\Controllers\UserDashboardController::class, 'settings'])->name('user.settings');
        Route::put('/profile/update', [\App\Http\Controllers\UserDashboardController::class, 'updateProfile'])->name('profile.update');
        Route::put('/profile/password', [\App\Http\Controllers\UserDashboardController::class, 'updatePassword'])->name('profile.password');

        // First-time profile completion (popup after OTP login)
        Route::post('/profile/complete', function (\Illuminate\Http\Request $request) {
            $validated = $request->validate([
                'name' => 'required|string|min:2|max:50',
                'email' => 'nullable|email|max:255|unique:users,email,' . auth()->id(),
            ]);

            $user = auth()->user();
            $user->name = $validated['name'];
            if (!empty($validated['email'])) {
                $user->email = $validated['email'];
            }
            $user->save();
            $user->markProfileCompleted();

            return response()->json(['success' => true, 'message' => 'Profile updated!']);
        })->name('profile.complete');

        // Payment routes
        Route::get('/payment/{plan}', [\App\Http\Controllers\PaymentController::class, 'showPayment'])->name('payment.show');
        Route::post('/payment/{payment}/verify', [\App\Http\Controllers\PaymentController::class, 'verifyPayment'])->name('payment.verify');
        Route::get('/payment/{payment}/status', [\App\Http\Controllers\PaymentController::class, 'paymentStatus'])->name('payment.status');

        // Razorpay direct checkout (AJAX from pricing page)
        Route::post('/payment/create-razorpay-order', [\App\Http\Controllers\PaymentController::class, 'createRazorpayOrder'])->name('payment.razorpay.create');
        Route::post('/payment/verify-razorpay', [\App\Http\Controllers\PaymentController::class, 'verifyRazorpayOrder'])->name('payment.razorpay.verify');

        // User Dashboard (home page after login)
        Route::get('/home', [HomeController::class, 'dashboard'])->name('dashboard');

        // AI Chat interface (for users only) - requires active subscription
        Route::get('/chat', [HomeController::class, 'index'])->middleware('subscription.active')->name('chat');

        // Web Chat API Routes (Claude-style) - uses web auth
        Route::prefix('api/chat')->group(function () {
            Route::get('/history', [\App\Http\Controllers\Api\ChatController::class, 'history']);
            Route::get('/{id}', [\App\Http\Controllers\Api\ChatController::class, 'show']);
            Route::post('/send', [\App\Http\Controllers\Api\ChatController::class, 'send']);
            Route::delete('/{id}', [\App\Http\Controllers\Api\ChatController::class, 'destroy']);
            Route::put('/{id}', [\App\Http\Controllers\Api\ChatController::class, 'update']);
        });

        // Image generation for Canvas mode (web auth)
        Route::post('/api/generate-image', [\App\Http\Controllers\ImageGenerationController::class, 'generate'])
            ->name('generate.image');

        // Image analysis endpoint with rate limiting and AI access control
        // Rate limit: 10 requests per minute per IP
        Route::post('/analyze-image', [ImageAnalysisController::class, 'analyze'])
            ->middleware(['throttle:10,1', 'ai.access'])
            ->name('analyze.image');

        // PDF analysis endpoint with rate limiting and AI access control
        // Rate limit: 10 requests per minute per IP
        Route::post('/analyze-pdf', [ImageAnalysisController::class, 'analyzePdf'])
            ->middleware(['throttle:10,1', 'ai.access'])
            ->name('analyze.pdf');

        // Text question endpoint with rate limiting and AI access control
        Route::post('/ask-question', [ImageAnalysisController::class, 'askQuestion'])
            ->middleware(['throttle:10,1', 'ai.access'])
            ->name('ask.question');

        // Streaming chat endpoint with rate limiting and AI access control
        Route::post('/chat/stream', [ImageAnalysisController::class, 'stream'])
            ->middleware(['throttle:10,1', 'ai.access'])
            ->name('chat.stream');

        // AI Chat endpoint
        Route::post('/ask-ai', [AiController::class, 'chat']);

        // Clear chat history
        Route::post('/chat/clear', [ImageAnalysisController::class, 'clearChat'])
            ->name('chat.clear');

        // ========================================
        // CONVERSATION ROUTES (ChatGPT-style)
        // ========================================
        Route::prefix('conversations')->name('conversations.')->group(function () {
            // Get or create active conversation
            Route::get('/active', [\App\Http\Controllers\ConversationController::class, 'getOrCreateConversation'])
                ->name('active');

            // Send message in conversation (with AI response streaming)
            Route::post('/message', [\App\Http\Controllers\ConversationController::class, 'sendMessage'])
                ->middleware(['throttle:10,1', 'ai.access'])
                ->name('message');

            // Create new conversation (New Chat button)
            Route::post('/new', [\App\Http\Controllers\ConversationController::class, 'createNewConversation'])
                ->name('new');

            // Get all user conversations (for sidebar)
            Route::get('/list', [\App\Http\Controllers\ConversationController::class, 'getUserConversations'])
                ->name('list');

            // Load specific conversation
            Route::get('/{conversation}', [\App\Http\Controllers\ConversationController::class, 'loadConversation'])
                ->name('load');

            // Delete conversation
            Route::delete('/{conversation}', [\App\Http\Controllers\ConversationController::class, 'deleteConversation'])
                ->name('delete');
        });

        // Prompt enhancement endpoint
        Route::post('/enhance-prompt', [ImageAnalysisController::class, 'enhancePrompt'])
            ->middleware(['throttle:20,1'])
            ->name('enhance.prompt');

        // Admin Panel (require admin role)
        Route::prefix('admin')->name('admin.')->middleware(['admin'])->group(function () {
            // Dashboard
            Route::get('/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');

            // Profile
            Route::get('/profile', [\App\Http\Controllers\Admin\ProfileController::class, 'edit'])->name('profile.edit');
            Route::put('/profile', [\App\Http\Controllers\Admin\ProfileController::class, 'update'])->name('profile.update');

            // 2FA Settings (Admin only)
            Route::get('/2fa/setup', [\App\Http\Controllers\Auth\Admin2FAController::class, 'showSetup'])->name('2fa.setup');
            Route::post('/2fa/enable', [\App\Http\Controllers\Auth\Admin2FAController::class, 'enable'])->name('2fa.enable');
            Route::post('/2fa/disable', [\App\Http\Controllers\Auth\Admin2FAController::class, 'disable'])->name('2fa.disable');

            // Website Settings
            Route::get('/settings', [\App\Http\Controllers\Admin\DashboardController::class, 'settings'])->name('settings');
            Route::post('/settings', [\App\Http\Controllers\Admin\DashboardController::class, 'updateSettings'])->name('settings.update');

            // AI Settings
            Route::get('/ai-settings', [\App\Http\Controllers\Admin\DashboardController::class, 'aiSettings'])->name('ai-settings');
            Route::post('/ai-settings', [\App\Http\Controllers\Admin\DashboardController::class, 'updateAiSettings'])->name('ai-settings.update');

            // Storage Settings (Cloudflare R2, S3, etc.)
            Route::prefix('storage-settings')->name('storage-settings.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Admin\StorageSettingsController::class, 'index'])->name('index');
                Route::get('/create', [\App\Http\Controllers\Admin\StorageSettingsController::class, 'create'])->name('create');
                Route::post('/', [\App\Http\Controllers\Admin\StorageSettingsController::class, 'store'])->name('store');
                Route::get('/{storageSetting}/edit', [\App\Http\Controllers\Admin\StorageSettingsController::class, 'edit'])->name('edit');
                Route::put('/{storageSetting}', [\App\Http\Controllers\Admin\StorageSettingsController::class, 'update'])->name('update');
                Route::delete('/{storageSetting}', [\App\Http\Controllers\Admin\StorageSettingsController::class, 'destroy'])->name('destroy');
                Route::get('/{storageSetting}/activate', [\App\Http\Controllers\Admin\StorageSettingsController::class, 'activate'])->name('activate');
                Route::get('/{storageSetting}/deactivate', [\App\Http\Controllers\Admin\StorageSettingsController::class, 'deactivate'])->name('deactivate');
                Route::post('/{storageSetting}/test', [\App\Http\Controllers\Admin\StorageSettingsController::class, 'testConnection'])->name('test');
            });
            Route::get('/storage-status', [\App\Http\Controllers\Admin\StorageSettingsController::class, 'getStatus'])->name('storage-status');

            // Payment Gateways Settings
            Route::get('/payment-gateways', [\App\Http\Controllers\Admin\DashboardController::class, 'paymentGateways'])->name('payment-gateways');
            Route::post('/payment-gateways', [\App\Http\Controllers\Admin\DashboardController::class, 'updatePaymentGateways'])->name('payment-gateways.update');
            Route::post('/payment-gateways/{gateway}/toggle', [\App\Http\Controllers\Admin\DashboardController::class, 'togglePaymentGateway'])->name('payment-gateways.toggle');

            // Users & Access Management
            Route::get('/users', [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('users');
            Route::get('/users/create', [\App\Http\Controllers\Admin\UserController::class, 'create'])->name('users.create');
            Route::post('/users', [\App\Http\Controllers\Admin\UserController::class, 'store'])->name('users.store');
            Route::post('/users/generate-code', [\App\Http\Controllers\Admin\UserController::class, 'generateCode'])->name('users.generate');
            Route::delete('/users/{code}', [\App\Http\Controllers\Admin\UserController::class, 'destroy'])->name('users.destroy');

            // User Management
            Route::get('/users/{user}/edit', [\App\Http\Controllers\Admin\UserController::class, 'edit'])->name('users.edit');
            Route::put('/users/{user}', [\App\Http\Controllers\Admin\UserController::class, 'updateUser'])->name('users.update');
            Route::post('/users/{user}/reset-tokens', [\App\Http\Controllers\Admin\UserController::class, 'resetTokens'])->name('users.reset-tokens');
            Route::post('/users/{user}/add-tokens', [\App\Http\Controllers\Admin\UserController::class, 'addTokens'])->name('users.add-tokens');
            Route::post('/users/{user}/toggle-active', [\App\Http\Controllers\Admin\UserController::class, 'toggleActive'])->name('users.toggle-active');
            Route::post('/users/{user}/change-plan', [\App\Http\Controllers\Admin\UserController::class, 'changePlan'])->name('users.change-plan');
            Route::delete('/users/{user}/delete', [\App\Http\Controllers\Admin\UserController::class, 'deleteUser'])->name('users.delete');

            // Pricing Management
            Route::resource('pricing', \App\Http\Controllers\Admin\PricingPlanController::class)->parameters([
                'pricing' => 'pricingPlan'
            ]);

            // Feature Management
            Route::resource('features', \App\Http\Controllers\Admin\FeatureController::class);

            // User Plans Management
            Route::resource('plans', \App\Http\Controllers\Admin\UserPlanController::class);

            // Activation Tokens Management
            Route::get('/tokens', [\App\Http\Controllers\Admin\ActivationTokenController::class, 'index'])->name('tokens.index');
            Route::post('/tokens/generate', [\App\Http\Controllers\Admin\ActivationTokenController::class, 'generate'])->name('tokens.generate');
            Route::post('/tokens/{token}/deactivate', [\App\Http\Controllers\Admin\ActivationTokenController::class, 'deactivate'])->name('tokens.deactivate');
            Route::delete('/tokens/{token}', [\App\Http\Controllers\Admin\ActivationTokenController::class, 'destroy'])->name('tokens.destroy');

            // IP Whitelist Management
            Route::get('/users/{user}/ip-whitelist', [\App\Http\Controllers\Admin\IpWhitelistController::class, 'index'])->name('users.ip-whitelist');
            Route::post('/users/{user}/ip-whitelist', [\App\Http\Controllers\Admin\IpWhitelistController::class, 'store'])->name('users.ip-whitelist.store');
            Route::post('/users/{user}/ip-whitelist/{ipWhitelist}/toggle', [\App\Http\Controllers\Admin\IpWhitelistController::class, 'toggle'])->name('users.ip-whitelist.toggle');
            Route::delete('/users/{user}/ip-whitelist/{ipWhitelist}', [\App\Http\Controllers\Admin\IpWhitelistController::class, 'destroy'])->name('users.ip-whitelist.destroy');

            // Usage Logs
            Route::get('/users/{user}/usage-logs', [\App\Http\Controllers\Admin\UsageLogController::class, 'index'])->name('users.usage-logs');
            Route::get('/usage-logs', [\App\Http\Controllers\Admin\UsageLogController::class, 'all'])->name('usage-logs.all');

            // AI Models Management
            Route::get('/ai-models', [\App\Http\Controllers\Admin\AiModelController::class, 'index'])->name('ai-models.index');
            Route::put('/ai-models/{aiModel}', [\App\Http\Controllers\Admin\AiModelController::class, 'update'])->name('ai-models.update');
            Route::post('/ai-models/{aiModel}/toggle', [\App\Http\Controllers\Admin\AiModelController::class, 'toggleActive'])->name('ai-models.toggle');

            // Whiteboard Video Settings
            Route::prefix('whiteboard-video')->name('whiteboard-video.')->group(function () {
                Route::get('/settings', [\App\Http\Controllers\Admin\WhiteboardVideoSettingsController::class, 'index'])->name('settings');
                Route::post('/settings', [\App\Http\Controllers\Admin\WhiteboardVideoSettingsController::class, 'update'])->name('settings.update');
            });

            // Feature-Specific AI Models Configuration
            Route::get('/feature-models', [\App\Http\Controllers\Admin\FeatureModelsController::class, 'index'])->name('feature-models.index');
            Route::post('/feature-models', [\App\Http\Controllers\Admin\FeatureModelsController::class, 'update'])->name('feature-models.update');

            // Quiz Generator Settings
            Route::prefix('quiz-generator')->name('quiz-generator.')->group(function () {
                Route::get('/settings', [\App\Http\Controllers\Admin\QuizGeneratorSettingsController::class, 'index'])->name('settings');
                Route::post('/settings', [\App\Http\Controllers\Admin\QuizGeneratorSettingsController::class, 'update'])->name('settings.update');
            });

            // AI Configuration Management (System Prompts & Usage Only)
            Route::prefix('ai-config')->name('ai-config.')->group(function () {
                // System Prompts
                Route::get('/prompts', [\App\Http\Controllers\Admin\AiConfigurationController::class, 'prompts'])->name('prompts');
                Route::post('/prompts/{prompt}', [\App\Http\Controllers\Admin\AiConfigurationController::class, 'updatePrompt'])->name('prompts.update');

                // Usage Statistics
                Route::get('/usage', [\App\Http\Controllers\Admin\AiConfigurationController::class, 'usage'])->name('usage');
                Route::get('/usage-data', [\App\Http\Controllers\Admin\AiConfigurationController::class, 'usageData'])->name('usage.data');
                Route::get('/usage/export', [\App\Http\Controllers\Admin\AiConfigurationController::class, 'exportUsage'])->name('usage.export');

                // REMOVED: Model Configuration (now handled per-feature)
                // Route::get('/models', [\App\Http\Controllers\Admin\AiConfigurationController::class, 'modelsConfig'])->name('models');
                // Route::post('/models', [\App\Http\Controllers\Admin\AiConfigurationController::class, 'updateModelsConfig'])->name('models.update');
            });

            // Payment Management (removed — plan-based system)
            // Route::get('/payments', [\App\Http\Controllers\Admin\PaymentController::class, 'index'])->name('payments.index');
            // Route::post('/payments/{payment}/confirm', [\App\Http\Controllers\Admin\PaymentController::class, 'confirm'])->name('payments.confirm');
            // Route::post('/payments/{payment}/reject', [\App\Http\Controllers\Admin\PaymentController::class, 'reject'])->name('payments.reject');

            // XML Import System (Admin Only)
            Route::prefix('xml-import')->name('xml-import.')->controller(\App\Http\Controllers\Admin\XmlImportController::class)->group(function () {
                Route::get('/', 'index')->name('index');                        // List all imports
                Route::post('/upload', 'upload')->name('upload');               // Upload and process XML
                Route::get('/{import}', 'show')->name('show');                  // View import details
                Route::get('/{import}/download-log', 'downloadLog')->name('download-log'); // Download import log
                Route::delete('/{import}', 'destroy')->name('destroy');         // Delete import record
            });

            // Notifications Management (Admin Only)
            Route::prefix('notifications')->name('notifications.')->controller(\App\Http\Controllers\Admin\NotificationController::class)->group(function () {
                Route::get('/', 'index')->name('index');                        // List all notifications
                Route::get('/create', 'create')->name('create');                // Show create form
                Route::post('/', 'store')->name('store');                       // Create notification
                Route::get('/{notification}', 'show')->name('show');            // View notification details
                Route::get('/{notification}/edit', 'edit')->name('edit');       // Edit notification
                Route::put('/{notification}', 'update')->name('update');        // Update notification
                Route::post('/{notification}/toggle', 'toggleActive')->name('toggle'); // Toggle active status
                Route::delete('/{notification}', 'destroy')->name('destroy');    // Delete notification
            });

            // Pages Management (Admin Only) - Dynamic CMS Pages
            Route::prefix('pages')->name('pages.')->controller(\App\Http\Controllers\Admin\PageController::class)->group(function () {
                Route::get('/', 'index')->name('index');                        // List all pages
                Route::get('/create', 'create')->name('create');                // Create page form
                Route::post('/', 'store')->name('store');                       // Store new page
                Route::get('/{page}/edit', 'edit')->name('edit');              // Edit page form
                Route::put('/{page}', 'update')->name('update');                // Update page
                Route::delete('/{page}', 'destroy')->name('destroy');           // Delete page
                Route::post('/{page}/toggle', 'toggleStatus')->name('toggle');  // Toggle active status
            });

            // Frontend Config Management (Admin Only)
            Route::prefix('frontend-configs')->name('frontend-configs.')->controller(\App\Http\Controllers\Admin\FrontendConfigController::class)->group(function () {
                Route::get('/', 'index')->name('index');                        // List all configs
                Route::post('/', 'store')->name('store');                       // Create/Update config
                Route::put('/{config}', 'update')->name('update');              // Update specific config
                Route::post('/{config}/toggle', 'toggle')->name('toggle');      // Toggle active status
                Route::delete('/{config}', 'destroy')->name('destroy');         // Delete config
                Route::post('/clear-cache', 'clearCache')->name('clear-cache'); // Clear config cache
            });

            // Mobile App Configuration (Admin Only)
            Route::prefix('mobile-app-config')->name('mobile-app-config.')->controller(\App\Http\Controllers\Admin\MobileAppConfigController::class)->group(function () {
                Route::get('/', 'index')->name('index');                        // View mobile app settings
Route::put('/update', 'update')->name('update');                // Update branding & app settings
                Route::put('/features', 'updateFeatures')->name('update-features'); // Update feature toggles
                Route::put('/screens', 'updateScreens')->name('update-screens'); // Update screen visibility
                Route::put('/version', 'updateVersion')->name('update-version'); // Update version settings
                Route::put('/ai', 'updateAiSettings')->name('update-ai');       // Update AI settings
                Route::put('/credits', 'updateCredits')->name('update-credits'); // Update credit settings
                Route::put('/content', 'updateContent')->name('update-content'); // Update content settings
                Route::put('/ads', 'updateAds')->name('update-ads');             // Update ad settings
                Route::post('/force-update', 'triggerForceUpdate')->name('trigger-force-update'); // Trigger force update
            });

            // Authentication Settings (Admin Only)
            Route::prefix('auth-settings')->name('auth-settings.')->controller(\App\Http\Controllers\Admin\AuthSettingsController::class)->group(function () {
                Route::get('/', 'index')->name('index');                        // View auth settings
                Route::put('/otp', 'updateOtpSettings')->name('update-otp');    // Update OTP settings
                Route::put('/email-otp', 'updateEmailOtpSettings')->name('update-email-otp'); // Update Email OTP settings
                Route::put('/login', 'updateLoginSettings')->name('update-login'); // Update login screen
                Route::put('/social', 'updateSocialSettings')->name('update-social'); // Update social login
                Route::put('/security', 'updateSecuritySettings')->name('update-security'); // Update security
            });

            // SEO Settings (Admin Only)
            Route::prefix('seo-settings')->name('seo-settings.')->controller(\App\Http\Controllers\Admin\SeoSettingsController::class)->group(function () {
                Route::get('/', 'index')->name('index');                        // View SEO settings
                Route::put('/update', 'update')->name('update');                // Update SEO settings
            });

            // Testimonials Management (Admin Only)
            Route::prefix('testimonials')->name('testimonials.')->controller(\App\Http\Controllers\Admin\TestimonialController::class)->group(function () {
                Route::get('/', 'index')->name('index');                                    // List all testimonials
                Route::post('/', 'store')->name('store');                                   // Create testimonial
                Route::put('/{testimonial}', 'update')->name('update');                     // Update testimonial
                Route::delete('/{testimonial}', 'destroy')->name('destroy');                // Delete testimonial
                Route::post('/{testimonial}/toggle', 'toggleStatus')->name('toggle');       // Toggle active status
            });

            // Contact Messages Management (Admin Only)
            Route::prefix('contact-messages')->name('contact-messages.')->controller(\App\Http\Controllers\Admin\ContactMessageController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/{id}', 'show')->name('show');
                Route::post('/{id}/reply', 'reply')->name('reply');
                Route::put('/{id}/status', 'updateStatus')->name('status');
                Route::delete('/{id}', 'destroy')->name('destroy');
            });

            // Exam Management (Admin Only)
            Route::resource('exams', \App\Http\Controllers\Admin\ExamController::class);
            Route::prefix('exams/{exam}/questions')->name('exam-questions.')->controller(\App\Http\Controllers\Admin\ExamQuestionController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::get('/{question}/edit', 'edit')->name('edit');
                Route::put('/{question}', 'update')->name('update');
                Route::delete('/{question}', 'destroy')->name('destroy');
                Route::post('/import', 'import')->name('import');
            });

            // Daily Challenges Management (Admin Only)
            Route::prefix('daily-challenges')->name('daily-challenges.')->controller(\App\Http\Controllers\Admin\DailyChallengeController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::put('/settings', 'updateSettings')->name('update-settings');
                Route::get('/{dailyChallenge}/edit', 'edit')->name('edit');
                Route::put('/{dailyChallenge}', 'update')->name('update');
                Route::delete('/{dailyChallenge}', 'destroy')->name('destroy');
                Route::post('/{dailyChallenge}/toggle', 'toggleActive')->name('toggle');
                Route::post('/generate-ai', 'generateWithAI')->name('generate-ai');
            });

            // Homepage Settings Management (Admin Only)
            Route::prefix('homepage-settings')->name('homepage-settings.')->controller(\App\Http\Controllers\Admin\HomepageSettingsController::class)->group(function () {
                Route::get('/', 'index')->name('index');                                    // List all homepage settings
                Route::put('/{setting}', 'update')->name('update');                         // Update specific setting
                Route::post('/bulk-update', 'bulkUpdate')->name('bulk-update');             // Bulk update settings
                Route::post('/upload-image', 'uploadImage')->name('upload-image');          // Upload image
                Route::post('/clear-cache', 'clearCache')->name('clear-cache');             // Clear settings cache
            });
        });
    });
});

        // ========================================
        // AI DOUBT SOLVER ROUTES (Student Tutor System)
        // ========================================
        Route::prefix('doubt-solver')->name('doubt.')->controller(\App\Http\Controllers\DoubtSolverController::class)->group(function () {
            // Text-based doubt solving
            Route::post('/solve-text', 'solveTextDoubt')->name('solve.text');
            
            // Image-based doubt solving (with file upload)
            Route::post('/solve-image', 'solveImageDoubt')->name('solve.image');
            
            // Image-based doubt solving (with base64)
            Route::post('/solve-image-base64', 'solveImageDoubtBase64')->name('solve.image.base64');
            
            // PDF-based doubt solving
            Route::post('/solve-pdf', 'solvePdfDoubt')->name('solve.pdf');
            
            // Session management
            Route::post('/set-subject', 'setSubject')->name('set.subject');
            Route::post('/clear-history', 'clearHistory')->name('clear.history');
            Route::get('/history', 'getHistory')->name('get.history');

            // NEW FEATURES - Quiz, Practice, Concepts, Hints, Summaries
            Route::post('/generate-quiz', 'generateQuiz')->name('generate.quiz');
            Route::post('/generate-practice', 'generatePracticeProblems')->name('generate.practice');
            Route::post('/explain-concept', 'explainConcept')->name('explain.concept');
            Route::post('/get-hint', 'getHint')->name('get.hint');
            Route::post('/summarize-topic', 'summarizeTopic')->name('summarize.topic');
        });


        // Note: Doubt solver features are now integrated into /chat page
        // API endpoints remain available for the chat interface to use

// End of main routes
