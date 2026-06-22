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
            config(['session.domain' => '.' . ltrim($main, '.')]);
            config(['session.secure' => $request->isSecure()]);
            config(['session.same_site' => 'lax']);
        }

        URL::forceRootUrl($request->getSchemeAndHttpHost());

        return $next($request);
    }
}
