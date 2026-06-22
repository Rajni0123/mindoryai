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
        if (config('domains.chat_use_main_domain')) {
            return false;
        }

        return filled(config('domains.chat'));
    }

    public static function isChatHost(?string $host = null): bool
    {
        $chatHost = config('domains.chat');

        if (! $chatHost) {
            return false;
        }

        return strcasecmp($host ?? request()->getHost(), $chatHost) === 0;
    }

    public static function baseUrl(): string
    {
        if (! self::isEnabled()) {
            return rtrim((string) (config('domains.main_url') ?: config('app.url')), '/');
        }

        $url = config('domains.chat_url');

        if ($url) {
            return rtrim((string) $url, '/');
        }

        return 'https://' . config('domains.chat');
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
