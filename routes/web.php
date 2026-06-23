<?php

use App\Http\Controllers\AccessController;
use App\Http\Controllers\AiController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ImageAnalysisController;
use App\Http\Controllers\PageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Main Website
|--------------------------------------------------------------------------
|
| Main website routes for landing, login, register
| Chat interface: chat.blinkstudy.in
| Admin panel: admin.blinkstudy.in
| API: api.blinkstudy.in
|
*/

// Main routes - Public website
// Production URL: yourdomain.com

// Landing page (admin subdomain → admin login)
Route::get('/', function (Request $request) {
    $host = strtolower((string) $request->getHost());
    if (str_starts_with($host, 'ad.') || str_starts_with($host, 'admin.')) {
        if (auth()->check() && auth()->user()->isAdmin()) {
            return redirect('/admin/dashboard');
        }

        return redirect('/admin/login');
    }

    return app(PageController::class)->landing();
})->name('home');

// ========================================
// UNIFIED LOGIN SYSTEM (Single URL for Admin & Users)
// ========================================
// IMPORTANT: Only ONE login URL for users; admin subdomain uses password login
Route::get('/login', function (Request $request) {
    $host = strtolower((string) $request->getHost());
    if (str_starts_with($host, 'ad.') || str_starts_with($host, 'admin.')) {
        return redirect('/admin/login');
    }

    return app(\App\Http\Controllers\Auth\UnifiedLoginController::class)->showLoginForm($request);
})->name('login');
Route::middleware('throttle:auth')->group(function () {
    Route::post('/login/send-otp', [\App\Http\Controllers\Auth\UnifiedLoginController::class, 'sendOTP'])->name('otp.send');
    Route::post('/login/verify-otp', [\App\Http\Controllers\Auth\UnifiedLoginController::class, 'verifyOTP'])->name('otp.verify');
    Route::post('/register', function () {
        return redirect()->route('login');
    })->name('register.submit');
});
Route::match(['get', 'post'], '/logout', [\App\Http\Controllers\Auth\UnifiedLoginController::class, 'logout'])->name('logout');

// Admin 2FA (After OTP verification, only for admin with 2FA enabled)
Route::get('/admin/verify-2fa', [\App\Http\Controllers\Auth\Admin2FAController::class, 'showVerify'])->name('admin.2fa.verify');
Route::post('/admin/verify-2fa', [\App\Http\Controllers\Auth\Admin2FAController::class, 'verify2FA'])->name('admin.2fa.verify.submit');

// Registration Routes - Disabled (Redirect to login)
Route::get('/register', function () {
    return redirect()->route('login');
})->name('register');

// Google OAuth Routes (Optional, if enabled)
Route::get('/auth/google/redirect', [\App\Http\Controllers\Auth\SocialAuthController::class, 'redirectToGoogle'])->name('google.redirect');
Route::get('/auth/google/callback', [\App\Http\Controllers\Auth\SocialAuthController::class, 'handleGoogleCallback'])->name('google.callback');

// ========================================
// LEGACY ROUTES - Redirect to unified login
// ========================================
// Admin login (email or mobile + password)
Route::get('/admin/login', [\App\Http\Controllers\Auth\AdminAuthController::class, 'showLoginForm'])->name('admin.login');
Route::middleware('throttle:auth')->group(function () {
    Route::post('/admin/login', [\App\Http\Controllers\Auth\AdminAuthController::class, 'login'])->name('admin.login.submit');
});

// Legacy admin URL → admin login
Route::get('/secure-admin/access', function () {
    return redirect()->route('admin.login');
});
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
            return redirect()->away(\App\Support\ChatSubdomainUrl::appUrl());
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

        return redirect()->away(\App\Support\ChatSubdomainUrl::appUrl())->with('success', 'Profile updated successfully!');
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
Route::get('/account-deletion', function() { return view('pages.account-deletion'); })->name('account.deletion');
Route::post('/account-deletion', [\App\Http\Controllers\AccountDeletionController::class, 'request'])->name('account.deletion.request');
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
        Route::put('/profile/password', [\App\Http\Controllers\UserDashboardController::class, 'updatePassword'])
            ->middleware('throttle:auth')
            ->name('profile.password');

        // First-time profile completion (popup after OTP login)
        Route::post('/profile/complete', [\App\Http\Controllers\ProfileCompletionController::class, 'store'])
            ->name('profile.complete');

        // Payment routes
        Route::get('/payment/{plan}', [\App\Http\Controllers\PaymentController::class, 'showPayment'])->name('payment.show');
        Route::post('/payment/{payment}/verify', [\App\Http\Controllers\PaymentController::class, 'verifyPayment'])->name('payment.verify');
        Route::get('/payment/{payment}/status', [\App\Http\Controllers\PaymentController::class, 'paymentStatus'])->name('payment.status');

        // Razorpay direct checkout (AJAX from pricing page) - OLD ROUTES (deprecated)
        // Route::post('/payment/create-razorpay-order', [\App\Http\Controllers\PaymentController::class, 'createRazorpayOrder'])->name('payment.razorpay.create');
        // Route::post('/payment/verify-razorpay', [\App\Http\Controllers\PaymentController::class, 'verifyRazorpayOrder'])->name('payment.razorpay.verify');

        // NEW: Razorpay routes using the new Plan model (for web session auth)
        Route::prefix('web/razorpay')->name('web.razorpay.')->group(function () {
            Route::post('/create-order', [\App\Http\Controllers\Api\RazorpayController::class, 'createOrder'])->name('create-order');
            Route::post('/create-pack-order', [\App\Http\Controllers\Api\RazorpayController::class, 'createPackOrder'])->name('create-pack-order');
            Route::post('/verify-payment', [\App\Http\Controllers\Api\RazorpayController::class, 'verifyPayment'])->name('verify-payment');
        });

        // Main domain only — chat.blinkstudy.in serves /chat via routes/chat.php
        if (\App\Support\ChatSubdomainUrl::isEnabled()) {
            Route::domain(config('domains.main'))->group(function () {
                Route::get('/chat', function () {
                    return redirect()->away(\App\Support\ChatSubdomainUrl::appUrl());
                })->middleware('subscription.active');
            });
        } else {
            require __DIR__ . '/includes/chat_application.php';
        }

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

            // Hybrid Retrieval Engine
            Route::get('/hybrid-retrieval', [\App\Http\Controllers\Admin\HybridRetrievalController::class, 'index'])->name('hybrid-retrieval');
            Route::post('/hybrid-retrieval/settings', [\App\Http\Controllers\Admin\HybridRetrievalController::class, 'updateSettings'])->name('hybrid-retrieval.settings');
            Route::post('/hybrid-retrieval/sources', [\App\Http\Controllers\Admin\HybridRetrievalController::class, 'storeKnowledgeSource'])->name('hybrid-retrieval.sources.store');
            Route::post('/hybrid-retrieval/sources/{knowledgeSource}/toggle', [\App\Http\Controllers\Admin\HybridRetrievalController::class, 'toggleKnowledgeSource'])->name('hybrid-retrieval.sources.toggle');
            Route::post('/hybrid-retrieval/test-exa', [\App\Http\Controllers\Admin\HybridRetrievalController::class, 'testExa'])->name('hybrid-retrieval.test-exa');

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

            // Account Deletion Requests Management (Admin Only)
            Route::prefix('deletion-requests')->name('deletion-requests.')->controller(\App\Http\Controllers\Admin\DeletionRequestController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/{deletionRequest}', 'show')->name('show');
                Route::patch('/{deletionRequest}/status', 'updateStatus')->name('update-status');
                Route::post('/{deletionRequest}/process', 'process')->name('process');
                Route::post('/{deletionRequest}/reject', 'reject')->name('reject');
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

// End of main routes
