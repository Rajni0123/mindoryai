<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps admin sessions and generated URLs on the current subdomain (ad.* / admin.*).
 */
class ConfigureAdminSubdomain
{
    public function handle(Request $request, Closure $next): Response
    {
        $host = strtolower((string) $request->getHost());
        $main = (string) config('domains.main', '');

        if ($main !== '' && (str_ends_with($host, $main) || str_starts_with($host, 'ad.') || str_starts_with($host, 'admin.'))) {
            $cookieDomain = '.' . ltrim($main, '.');
            config([
                'session.domain' => $cookieDomain,
                'session.secure' => $request->isSecure() || filter_var(config('session.secure'), FILTER_VALIDATE_BOOLEAN),
                'session.same_site' => config('session.same_site', 'lax'),
            ]);
        }

        URL::forceRootUrl($request->getSchemeAndHttpHost());

        return $next($request);
    }
}
