<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\ServiceProvider;

class OAuthConfigServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * Load Google OAuth configuration from database if available,
     * overriding environment values.
     */
    public function boot(): void
    {
        // Only load from database if not in console commands that don't need it
        if ($this->app->runningInConsole() && !$this->app->runningUnitTests()) {
            return;
        }

        try {
            // Check if settings table exists (avoid errors during migration)
            if (\Schema::hasTable('settings')) {
                // Load Google OAuth config from database
                $googleClientId = Setting::get('google_client_id');
                $googleClientSecret = Setting::get('google_client_secret');
                $googleRedirectUrl = Setting::get('google_redirect_url');

                // Override config if database values exist
                if ($googleClientId) {
                    config(['services.google.client_id' => $googleClientId]);
                }
                if ($googleClientSecret) {
                    config(['services.google.client_secret' => $googleClientSecret]);
                }
                if ($googleRedirectUrl) {
                    config(['services.google.redirect' => $googleRedirectUrl]);
                }
            }
        } catch (\Exception $e) {
            // Silently fail if database is not available
            // This prevents errors during initial setup or migrations
        }
    }
}
