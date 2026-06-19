<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class CacheResponse
{
    /**
     * Handle an incoming request and cache the response for public pages.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, int $minutes = 60): Response
    {
        // Only cache GET requests
        if (!$request->isMethod('GET')) {
            return $next($request);
        }

        // Don't cache authenticated requests
        if ($request->user()) {
            return $next($request);
        }

        // Generate cache key based on URL and query parameters
        $cacheKey = 'response_cache_' . md5($request->fullUrl());

        // Try to get cached response
        if (Cache::has($cacheKey)) {
            $cachedResponse = Cache::get($cacheKey);
            return response($cachedResponse['content'])
                ->withHeaders($cachedResponse['headers'] ?? [])
                ->header('X-Cache', 'HIT');
        }

        // Get fresh response
        $response = $next($request);

        // Only cache successful responses
        if ($response->isSuccessful()) {
            Cache::put($cacheKey, [
                'content' => $response->getContent(),
                'headers' => [
                    'Content-Type' => $response->headers->get('Content-Type'),
                ],
            ], now()->addMinutes($minutes));

            $response->header('X-Cache', 'MISS');
        }

        return $response;
    }
}
