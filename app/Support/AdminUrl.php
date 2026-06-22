<?php

namespace App\Support;

/**
 * Admin panel URLs that stay on the current host (ad.blinkstudy.in).
 */
final class AdminUrl
{
    public static function route(string $name, mixed $parameters = []): string
    {
        return route($name, $parameters, false);
    }

    public static function path(string $path): string
    {
        return '/' . ltrim($path, '/');
    }
}
