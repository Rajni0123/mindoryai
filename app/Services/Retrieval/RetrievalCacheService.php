<?php

namespace App\Services\Retrieval;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * Redis-backed cache for retrieval pipeline with Laravel cache fallback.
 */
class RetrievalCacheService
{
    public function __construct(
        protected RetrievalSettingsService $settings,
    ) {}

    public function remember(string $type, string $key, callable $callback, ?int $ttl = null): mixed
    {
        if (! $this->settings->isRedisCacheEnabled()) {
            return $callback();
        }

        $ttl = $ttl ?? config("retrieval.cache.ttl.{$type}", 3600);
        $cacheKey = config('retrieval.cache.prefix', 'retrieval:') . $type . ':' . md5($key);

        try {
            if ($this->useRedis()) {
                $cached = Redis::get($cacheKey);
                if ($cached !== null) {
                    return json_decode($cached, true);
                }

                $value = $callback();
                Redis::setex($cacheKey, $ttl, json_encode($value));

                return $value;
            }
        } catch (\Throwable $e) {
            Log::warning('Retrieval Redis cache failed, falling back', ['error' => $e->getMessage()]);
        }

        return Cache::remember($cacheKey, $ttl, $callback);
    }

    public function forget(string $type, string $key): void
    {
        $cacheKey = config('retrieval.cache.prefix', 'retrieval:') . $type . ':' . md5($key);

        try {
            if ($this->useRedis()) {
                Redis::del($cacheKey);
            }
        } catch (\Throwable) {
            // noop
        }

        Cache::forget($cacheKey);
    }

    private function useRedis(): bool
    {
        return config('cache.default') === 'redis' || extension_loaded('redis');
    }
}
