<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class ResponseCache
{
    /**
     * Handle an incoming request with aggressive caching
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, int $minutes = 10): Response
    {
        // Only cache GET requests
        if (!$request->isMethod('GET')) {
            return $next($request);
        }

        // Don't cache authenticated requests
        if ($request->user()) {
            return $next($request);
        }

        // Generate cache key
        $cacheKey = 'route_cache_' . md5($request->fullUrl());

        // Try to get cached response
        $cachedResponse = Cache::get($cacheKey);

        if ($cachedResponse) {
            return response($cachedResponse['content'], 200)
                ->withHeaders($cachedResponse['headers'])
                ->header('X-Cache-Status', 'HIT')
                ->header('X-Cache-Age', time() - $cachedResponse['timestamp']);
        }

        // Get fresh response
        $response = $next($request);

        // Cache successful responses
        if ($response->isSuccessful() && !$response->headers->has('Cache-Control')) {
            Cache::put($cacheKey, [
                'content' => $response->getContent(),
                'headers' => [
                    'Content-Type' => $response->headers->get('Content-Type'),
                    'Cache-Control' => 'public, max-age=' . ($minutes * 60),
                ],
                'timestamp' => time(),
            ], now()->addMinutes($minutes));

            $response->header('X-Cache-Status', 'MISS');
        }

        // Add cache headers
        $response->header('Cache-Control', 'public, max-age=' . ($minutes * 60));

        return $response;
    }
}
