<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class OptimizePerformance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:optimize
                            {--clear : Clear all caches before optimizing}
                            {--warm : Warm up application caches after optimizing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimize application performance by caching config, routes, views, and warming up caches';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting BlinkStudy Performance Optimization...');
        $this->newLine();

        // Step 1: Clear caches if requested
        if ($this->option('clear')) {
            $this->clearCaches();
        }

        // Step 2: Cache configuration
        $this->optimizeConfiguration();

        // Step 3: Cache routes
        $this->optimizeRoutes();

        // Step 4: Cache views
        $this->optimizeViews();

        // Step 5: Warm up caches if requested
        if ($this->option('warm')) {
            $this->warmUpCaches();
        }

        $this->newLine();
        $this->info('Performance optimization complete!');
        $this->newLine();

        // Display recommendations
        $this->displayRecommendations();

        return Command::SUCCESS;
    }

    /**
     * Clear all caches
     */
    private function clearCaches(): void
    {
        $this->comment('Clearing all caches...');

        Artisan::call('config:clear');
        $this->line('  Config cache cleared');

        Artisan::call('route:clear');
        $this->line('  Route cache cleared');

        Artisan::call('view:clear');
        $this->line('  View cache cleared');

        Artisan::call('cache:clear');
        $this->line('  Application cache cleared');

        Artisan::call('event:clear');
        $this->line('  Event cache cleared');

        $this->info('All caches cleared successfully.');
        $this->newLine();
    }

    /**
     * Cache configuration files
     */
    private function optimizeConfiguration(): void
    {
        $this->comment('Caching configuration...');

        try {
            Artisan::call('config:cache');
            $this->info('Configuration cached successfully.');
        } catch (\Exception $e) {
            $this->error('Failed to cache config: ' . $e->getMessage());
            $this->warn('Tip: Make sure you\'re not using env() in your config files.');
        }
    }

    /**
     * Cache routes
     */
    private function optimizeRoutes(): void
    {
        $this->comment('Caching routes...');

        try {
            Artisan::call('route:cache');
            $this->info('Routes cached successfully.');
        } catch (\Exception $e) {
            $this->error('Failed to cache routes: ' . $e->getMessage());
            $this->warn('Tip: Make sure all routes use controller classes, not closures.');
        }
    }

    /**
     * Cache views
     */
    private function optimizeViews(): void
    {
        $this->comment('Caching views...');

        try {
            Artisan::call('view:cache');
            $this->info('Views cached successfully.');
        } catch (\Exception $e) {
            $this->error('Failed to cache views: ' . $e->getMessage());
        }
    }

    /**
     * Warm up application caches
     */
    private function warmUpCaches(): void
    {
        $this->comment('Warming up application caches...');

        // Warm up FrontendConfig cache
        try {
            \App\Models\FrontendConfig::getAllConfigs();
            $this->line('  FrontendConfig cache warmed');
        } catch (\Exception $e) {
            $this->warn('  Failed to warm FrontendConfig: ' . $e->getMessage());
        }

        // Warm up PricingPlan cache
        try {
            \App\Models\PricingPlan::getActivePlans();
            $this->line('  PricingPlan cache warmed');
        } catch (\Exception $e) {
            $this->warn('  Failed to warm PricingPlan: ' . $e->getMessage());
        }

        // Warm up Feature cache
        try {
            \App\Models\Feature::getActiveFeatures();
            $this->line('  Feature cache warmed');
        } catch (\Exception $e) {
            $this->warn('  Failed to warm Feature: ' . $e->getMessage());
        }

        // Warm up HomepageSetting cache
        try {
            \App\Models\HomepageSetting::getAllCached();
            $this->line('  HomepageSetting cache warmed');
        } catch (\Exception $e) {
            $this->warn('  Failed to warm HomepageSetting: ' . $e->getMessage());
        }

        // Warm up OTP settings cache
        try {
            Cache::remember('otp_service_settings', 300, function () {
                return [
                    'length' => (int) \App\Models\FrontendConfig::getValue('auth.otp.length', 4),
                    'expiry_minutes' => (int) \App\Models\FrontendConfig::getValue('auth.otp.expiry_minutes', 5),
                    'max_attempts' => (int) \App\Models\FrontendConfig::getValue('auth.otp.max_attempts', 3),
                ];
            });
            $this->line('  OTP settings cache warmed');
        } catch (\Exception $e) {
            $this->warn('  Failed to warm OTP settings: ' . $e->getMessage());
        }

        $this->info('Application caches warmed successfully.');
    }

    /**
     * Display performance recommendations
     */
    private function displayRecommendations(): void
    {
        $this->comment('Performance Recommendations:');
        $this->newLine();

        // Check cache driver
        $cacheDriver = config('cache.default');
        if ($cacheDriver === 'file') {
            $this->warn('  [CACHE] Using file driver. Consider using Redis for better performance.');
        } elseif ($cacheDriver === 'database') {
            $this->error('  [CACHE] Using database driver. This is SLOW! Switch to file or Redis.');
        } else {
            $this->info("  [CACHE] Using {$cacheDriver} driver. Good choice!");
        }

        // Check session driver
        $sessionDriver = config('session.driver');
        if ($sessionDriver === 'database') {
            $this->warn('  [SESSION] Using database driver. Consider using file or Redis for better performance.');
        } else {
            $this->info("  [SESSION] Using {$sessionDriver} driver. Good choice!");
        }

        // Check debug mode
        if (config('app.debug')) {
            $this->warn('  [DEBUG] Debug mode is ON. Disable in production for better performance.');
        } else {
            $this->info('  [DEBUG] Debug mode is OFF. Good!');
        }

        // Check env
        if (config('app.env') !== 'production') {
            $this->warn('  [ENV] App is not in production mode. Set APP_ENV=production.');
        } else {
            $this->info('  [ENV] App is in production mode. Good!');
        }

        $this->newLine();
        $this->info('Run this command after every deployment: php artisan app:optimize --warm');
    }
}
