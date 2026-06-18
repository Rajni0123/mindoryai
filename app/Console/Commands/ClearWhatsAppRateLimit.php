<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

class ClearWhatsAppRateLimit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rate-limit:clear-whatsapp';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all WhatsApp OTP rate limits for testing';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔓 Clearing WhatsApp OTP rate limits...');

        // Get the cache store
        $cache = Cache::getStore();

        $clearedCount = 0;

        // Try different cache drivers
        if (method_exists($cache, 'getRedis')) {
            // Redis driver
            $redis = $cache->getRedis();
            $prefix = config('cache.prefix') . 'illuminate:rate limiter:';

            // Get all keys with the rate limiter prefix
            $keys = $redis->keys($prefix . 'whatsapp-otp-send:*');

            foreach ($keys as $key) {
                $redis->del($key);
                $clearedCount++;
            }

        } elseif (method_exists($cache, 'getDatabase')) {
            // Database driver
            $database = $cache->getDatabase();
            $prefix = config('cache.prefix');

            $records = $database->table(config('cache.stores.database.table'))
                ->where('key', 'like', $prefix . '%rate limiter:whatsapp-otp-send:%')
                ->get();

            foreach ($records as $record) {
                $database->table(config('cache.stores.database.table'))
                    ->where('key', $record->key)
                    ->delete();
                $clearedCount++;
            }

        } elseif (method_exists($cache, 'getFilesystem')) {
            // File driver - clear all rate limiter cache
            $cacheDirectory = storage_path('framework/cache/data');

            if (is_dir($cacheDirectory)) {
                $files = glob($cacheDirectory . '/*');

                foreach ($files as $file) {
                    if (is_file($file)) {
                        $content = file_get_contents($file);

                        // Check if file contains rate limiter data for WhatsApp
                        if (str_contains($content, 'whatsapp-otp-send')) {
                            @unlink($file);
                            $clearedCount++;
                        }
                    }
                }
            }
        }

        // Also try to clear using Laravel's cache clear for the rate limiter
        RateLimiter::clear('whatsapp-otp-send:');

        $this->info("✅ Cleared {$clearedCount} rate limit entries for WhatsApp OTP");
        $this->info('📱 You can now send WhatsApp OTP requests without rate limiting');

        return Command::SUCCESS;
    }
}
