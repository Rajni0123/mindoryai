<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class RateLimitServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    protected function configureRateLimiting(): void
    {
        $tooMany = function (Request $request, array $headers) {
            $retryAfter = (int) ($headers['Retry-After'] ?? 60);

            return response()->json([
                'success' => false,
                'message' => 'Too many requests. Please try again later.',
                'retry_after' => $retryAfter,
            ], 429, array_merge($headers, [
                'Retry-After' => (string) $retryAfter,
            ]));
        };

        // General API — 60 requests per minute per IP
        RateLimiter::for('api', function (Request $request) use ($tooMany) {
            return Limit::perMinute(60)
                ->by('api|' . $request->ip())
                ->response($tooMany);
        });

        // Auth — 5 requests per 15 minutes per IP
        RateLimiter::for('auth', function (Request $request) use ($tooMany) {
            return Limit::perMinutes(15, 5)
                ->by('auth|' . $request->ip())
                ->response($tooMany);
        });

        // AI / LLM — 10 requests per minute per authenticated user (IP fallback)
        RateLimiter::for('ai', function (Request $request) use ($tooMany) {
            $key = $request->user()
                ? 'user:' . $request->user()->id
                : 'ip:' . $request->ip();

            return Limit::perMinute(10)
                ->by('ai|' . $key)
                ->response($tooMany);
        });

        // File uploads — 5 requests per minute per IP
        RateLimiter::for('upload', function (Request $request) use ($tooMany) {
            return Limit::perMinute(5)
                ->by('upload|' . $request->ip())
                ->response($tooMany);
        });
    }
}
