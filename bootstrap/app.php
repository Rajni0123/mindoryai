<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            if (env('CHAT_SUBDOMAIN')) {
                Illuminate\Support\Facades\Route::middleware('web')
                    ->group(base_path('routes/chat.php'));
            }
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Cloudflare / reverse proxy — required for HTTPS sessions behind proxy
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'ip.whitelist' => \App\Http\Middleware\IpWhitelistMiddleware::class,
            'access.check' => \App\Http\Middleware\CheckAccess::class,
            'ai.access' => \App\Http\Middleware\AiAccessControl::class,
            'admin' => \App\Http\Middleware\EnsureAdminRole::class,
            'user.role' => \App\Http\Middleware\EnsureUserRole::class,
            'subscription.active' => \App\Http\Middleware\CheckSubscriptionActive::class,
            'maintenance' => \App\Http\Middleware\CheckMaintenanceMode::class,
            'admin.ip.restrict' => \App\Http\Middleware\AdminIpRestrict::class,
            'check.feature' => \App\Http\Middleware\CheckFeatureLimit::class,
            'admin.only' => \App\Http\Middleware\AdminOnly::class,
            'smartcache' => \App\Http\Middleware\SmartCacheMiddleware::class,
            'throttle.auth' => \Illuminate\Routing\Middleware\ThrottleRequests::class . ':auth',
            'throttle.ai' => \Illuminate\Routing\Middleware\ThrottleRequests::class . ':ai',
            'throttle.upload' => \Illuminate\Routing\Middleware\ThrottleRequests::class . ':upload',
        ]);

        // Apply maintenance mode check globally to web routes
        $middleware->web(append: [
            \App\Http\Middleware\CheckMaintenanceMode::class,
        ]);

        // Apply Sanctum stateful middleware to API routes for SPA authentication
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);
    })
    ->withProviders([
        \App\Providers\AppServiceProvider::class,
        \App\Providers\RateLimitServiceProvider::class,
        \App\Providers\OAuthConfigServiceProvider::class,
        \App\Providers\SmartCacheServiceProvider::class,
    ])
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->renderable(function (\Illuminate\Validation\ValidationException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                \App\Support\ApiValidator::logFailure($request, $e->errors());

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid input.',
                    'errors' => $e->errors(),
                ], 400);
            }
        });

        $exceptions->reportable(function (\Throwable $e) {
            try {
                \Illuminate\Support\Facades\Log::error('Application exception', [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
            } catch (\Throwable $logError) {
                // Never break the app if logging fails (permissions, missing disk, etc.).
            }
        });
    })->create();
