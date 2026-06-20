<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class ChatSubdomainUrl
{
    public static function baseUrl(): string
    {
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
        $expiresAt = AuthTokenService::expirationMinutes()
            ? now()->addMinutes(AuthTokenService::expirationMinutes())
            : null;
        $authToken = $user->createToken('web-chat-transfer', ['web-chat'], $expiresAt)->plainTextToken;
        $tokenHash = hash('sha256', $authToken);
        Cache::put("chat_auth_transfer:{$tokenHash}", $user->id, now()->addMinutes(5));

        return self::baseUrl() . '?auth_token=' . urlencode($authToken);
    }
}
