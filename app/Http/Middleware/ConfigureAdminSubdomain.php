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
        $main = ltrim((string) config('domains.main', ''), '.');
        $isAdminHost = str_starts_with($host, 'ad.') || str_starts_with($host, 'admin.');
        $onMainTree = $main !== '' && (str_ends_with($host, $main) || $host === $main);

        if ($isAdminHost || $onMainTree) {
            $rootDomain = $main !== '' ? $main : $this->deriveRootDomain($host);

            if ($rootDomain !== '') {
                config([
                    'session.domain' => '.' . $rootDomain,
                    'session.secure' => $this->shouldUseSecureCookies($request),
                    'session.same_site' => config('session.same_site', 'lax'),
                ]);
            }
        }

        if ($this->requestIsHttps($request)) {
            URL::forceScheme('https');
        }

        URL::forceRootUrl($request->getSchemeAndHttpHost());

        return $next($request);
    }

    private function deriveRootDomain(string $host): string
    {
        if (preg_match('/^(?:ad|admin|api|chat|files)\.(.+)$/i', $host, $matches)) {
            return strtolower($matches[1]);
        }

        return $host;
    }

    private function requestIsHttps(Request $request): bool
    {
        return $request->isSecure()
            || strtolower((string) $request->header('X-Forwarded-Proto', '')) === 'https'
            || strtolower((string) $request->header('X-Forwarded-Ssl', '')) === 'on';
    }

    private function shouldUseSecureCookies(Request $request): bool
    {
        if ($this->requestIsHttps($request)) {
            return true;
        }

        return filter_var(config('session.secure'), FILTER_VALIDATE_BOOLEAN);
    }
}
