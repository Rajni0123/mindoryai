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
        // Subdomain routes disabled for local development
        // then: function () {
        //     Route::middleware('web')
        //         ->group(base_path('routes/chat.php'));
        //     Route::middleware('web')
        //         ->group(base_path('routes/admin.php'));
        // },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'ip.whitelist' => \App\Http\Middleware\IpWhitelistMiddleware::class,
            'access.check' => \App\Http\Middleware\CheckAccess::class,
            'ai.access' => \App\Http\Middleware\AiAccessControl::class,
            'admin' => \App\Http\Middleware\EnsureAdminRole::class, // Updated to use new admin middleware
            'user.role' => \App\Http\Middleware\EnsureUserRole::class, // New user role middleware
            'subscription.active' => \App\Http\Middleware\CheckSubscriptionActive::class,
            'maintenance' => \App\Http\Middleware\CheckMaintenanceMode::class,
            'admin.ip.restrict' => \App\Http\Middleware\AdminIpRestrict::class, // Admin IP restriction
            'check.feature' => \App\Http\Middleware\CheckFeatureLimit::class, // Feature daily limit check
            'admin.only' => \App\Http\Middleware\AdminOnly::class, // Admin role enforcement
            'smartcache' => \App\Http\Middleware\SmartCacheMiddleware::class, // Smart Cache for AI responses
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
        \App\Providers\OAuthConfigServiceProvider::class,
        \App\Providers\SmartCacheServiceProvider::class,
    ])
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
