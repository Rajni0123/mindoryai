<?php

namespace App\Support;

class SensitiveConfigFilter
{
    private const SENSITIVE_TOKEN_FRAGMENTS = [
        'access_token',
        'refresh_token',
        'bearer_token',
        'auth_token',
        'api_token',
        'id_token',
        'reset_token',
    ];

    private const SENSITIVE_FRAGMENTS = [
        'api_key',
        'api_secret',
        'client_secret',
        'webhook_secret',
        'private_key',
        'password',
        'authkey',
        'auth_key',
        'secret_key',
        'renflair_api_key',
    ];

    public static function isSensitiveKey(string $key): bool
    {
        $lower = strtolower($key);

        foreach (self::SENSITIVE_FRAGMENTS as $fragment) {
            if (str_contains($lower, $fragment)) {
                return true;
            }
        }

        if (str_ends_with($lower, '_secret') || str_ends_with($lower, '.secret')) {
            return true;
        }

        foreach (self::SENSITIVE_TOKEN_FRAGMENTS as $fragment) {
            if (str_contains($lower, $fragment)) {
                return true;
            }
        }

        return false;
    }

    public static function filterArray(array $configs): array
    {
        $filtered = [];

        foreach ($configs as $key => $value) {
            if (self::isSensitiveKey((string) $key)) {
                continue;
            }

            $filtered[$key] = is_array($value)
                ? self::filterArray($value)
                : $value;
        }

        return $filtered;
    }
}
