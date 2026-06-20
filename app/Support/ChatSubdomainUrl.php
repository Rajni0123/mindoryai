<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class ChatSubdomainUrl
{
    /**
     * Production uses chat.blinkstudy.in. Local dev can set CHAT_USE_MAIN_DOMAIN=true
     * so chat runs on the same host as php artisan serve (no separate subdomain).
     */
    public static function isEnabled(): bool
    {
        if (filter_var(env('CHAT_USE_MAIN_DOMAIN', false), FILTER_VALIDATE_BOOLEAN)) {
            return false;
        }

        return filled(env('CHAT_SUBDOMAIN'));
    }

    public static function baseUrl(): string
    {
        if (! self::isEnabled()) {
            return rtrim((string) (env('MAIN_DOMAIN_URL') ?: config('app.url')), '/');
        }

        $url = env('CHAT_SUBDOMAIN_URL');

        if ($url) {
            return rtrim((string) $url, '/');
        }

        $subdomain = env('CHAT_SUBDOMAIN', 'chat.' . env('MAIN_DOMAIN', 'localhost'));

        return 'https://' . $subdomain;
    }

    public static function appPath(): string
    {
        return '/chat';
    }

    public static function appUrl(): string
    {
        return self::baseUrl() . self::appPath();
    }

    public static function transferUrl(User $user): string
    {
        if (! self::isEnabled()) {
            return self::appUrl();
        }

        $expiresAt = AuthTokenService::expirationMinutes()
            ? now()->addMinutes(AuthTokenService::expirationMinutes())
            : null;
        $authToken = $user->createToken('web-chat-transfer', ['web-chat'], $expiresAt)->plainTextToken;
        $tokenHash = hash('sha256', $authToken);
        Cache::put("chat_auth_transfer:{$tokenHash}", $user->id, now()->addMinutes(5));

        return self::baseUrl() . '?auth_token=' . urlencode($authToken);
    }
}
