<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Apply optimal AI defaults from config when env vars are set
        config([
            'openai.model' => config('ai.chat_model', 'gpt-4o-mini'),
        ]);

        if ($this->app->environment('production')) {
            $appKey = (string) config('app.key');
            $minLength = (int) config('auth-security.app_key_min_length', 32);

            // APP_KEY format: base64:.... — validate decoded secret length
            if (str_starts_with($appKey, 'base64:')) {
                $decoded = base64_decode(substr($appKey, 7), true);
                if ($decoded === false || strlen($decoded) < $minLength) {
                    throw new \RuntimeException(
                        'APP_KEY must decode to at least ' . $minLength . ' bytes. Run: php artisan key:generate'
                    );
                }
            } elseif (strlen($appKey) < $minLength) {
                throw new \RuntimeException(
                    'APP_KEY must be at least ' . $minLength . ' characters. Run: php artisan key:generate'
                );
            }
        }
    }
}
