<?php

namespace App\Support;

use App\Models\User;
use Laravel\Sanctum\NewAccessToken;

class AuthTokenService
{
    public static function expirationMinutes(): ?int
    {
        $expiration = config('sanctum.expiration');

        return $expiration !== null ? (int) $expiration : null;
    }

    public static function createAccessToken(User $user, string $name = 'mobile-app', array $abilities = ['*']): NewAccessToken
    {
        $minutes = self::expirationMinutes();
        $expiresAt = $minutes ? now()->addMinutes($minutes) : null;

        return $user->createToken($name, $abilities, $expiresAt);
    }

    /**
     * Rotate the current bearer token: revoke old, issue new with fresh expiry.
     */
    public static function refreshAccessToken(User $user, string $tokenName = 'mobile-app'): NewAccessToken
    {
        $currentToken = $user->currentAccessToken();

        if ($currentToken) {
            $currentToken->delete();
        }

        return self::createAccessToken($user, $tokenName);
    }
}
