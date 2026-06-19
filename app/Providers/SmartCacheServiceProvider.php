<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Cache\TextNormalizer;
use App\Services\Cache\SmartGreetingFilter;
use App\Services\Cache\FollowUpDetector;
use App\Services\Cache\MessageQualityGate;
use App\Services\Cache\SmartCacheService;
use App\Services\Cache\CacheAnalyticsService;

class SmartCacheServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register as singletons for performance
        $this->app->singleton(TextNormalizer::class, function ($app) {
            return new TextNormalizer();
        });

        $this->app->singleton(SmartGreetingFilter::class, function ($app) {
            return new SmartGreetingFilter(
                $app->make(TextNormalizer::class)
            );
        });

        $this->app->singleton(FollowUpDetector::class, function ($app) {
            return new FollowUpDetector(
                $app->make(TextNormalizer::class)
            );
        });

        $this->app->singleton(MessageQualityGate::class, function ($app) {
            return new MessageQualityGate(
                $app->make(TextNormalizer::class)
            );
        });

        $this->app->singleton(CacheAnalyticsService::class, function ($app) {
            return new CacheAnalyticsService();
        });

        $this->app->singleton(SmartCacheService::class, function ($app) {
            return new SmartCacheService(
                $app->make(TextNormalizer::class),
                $app->make(SmartGreetingFilter::class),
                $app->make(FollowUpDetector::class),
                $app->make(MessageQualityGate::class),
                $app->make(CacheAnalyticsService::class)
            );
        });

        // Register alias for easier access
        $this->app->alias(SmartCacheService::class, 'smartcache');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register middleware alias
        $this->app['router']->aliasMiddleware('smartcache', \App\Http\Middleware\SmartCacheMiddleware::class);

        // Publish config (if this were a package)
        // $this->publishes([
        //     __DIR__.'/../../config/smartcache.php' => config_path('smartcache.php'),
        // ], 'smartcache-config');

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\CacheSetupCommand::class,
                \App\Console\Commands\CacheStatsCommand::class,
                \App\Console\Commands\CacheTestCommand::class,
                \App\Console\Commands\CacheSeedCommand::class,
                \App\Console\Commands\CacheCleanupCommand::class,
                \App\Console\Commands\CachePurgeGarbageCommand::class,
            ]);
        }
    }
}
