<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'login/*',             // All login routes (OTP send/verify)
        'admin/login',         // Admin password login (rate-limited; session cookie flaky on ad.*)
        'admin/verify-2fa',    // Admin 2FA verification
        'api/*',               // All API routes
        'logout',              // Allow logout when session/CSRF token expired
    ];
}
