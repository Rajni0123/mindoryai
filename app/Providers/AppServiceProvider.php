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
        // Laravel's default remember me token duration is 5 years (2,628,000 minutes)
        // Combined with our extended session lifetime (525600 minutes = 1 year),
        // users will stay logged in for a very long time, similar to ChatGPT's behavior
    }
}
