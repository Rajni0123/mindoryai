<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Send /admin/* on the main site to the dedicated admin subdomain (keeps one host + session).
 */
class RedirectAdminToSubdomain
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->is('admin') && ! $request->is('admin/*')) {
            return $next($request);
        }

        $adminUrl = (string) config('domains.admin_url', '');
        if ($adminUrl === '') {
            return $next($request);
        }

        $adminHost = strtolower((string) parse_url($adminUrl, PHP_URL_HOST));
        $currentHost = strtolower((string) $request->getHost());

        if ($adminHost === '' || $adminHost === $currentHost) {
            return $next($request);
        }

        $target = rtrim($adminUrl, '/') . $request->getRequestUri();

        return redirect()->away($target, 302);
    }
}
