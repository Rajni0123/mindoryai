<?php

namespace App\Http\Middleware;

use App\Models\WhitelistedIp;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class IpWhitelistMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get client IP address
        $clientIp = $request->ip();

        // Skip IP check for admin routes - admin can access from anywhere
        if ($request->is('admin/*') || $request->is('admin')) {
            return $next($request);
        }

        // Check if IP whitelisting is enabled (read from env directly)
        $ipWhitelistEnabled = env('IP_WHITELIST_ENABLED', 'false');
        if ($ipWhitelistEnabled === 'false' || $ipWhitelistEnabled === false) {
            return $next($request);
        }

        // Cache the whitelist check for 5 minutes to improve performance
        $isWhitelisted = Cache::remember(
            "ip_whitelist_{$clientIp}",
            now()->addMinutes(5),
            function () use ($clientIp) {
                return WhitelistedIp::isWhitelisted($clientIp);
            }
        );

        // If IP is not whitelisted, return 403
        if (!$isWhitelisted) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Access denied. Your IP address is not authorized.',
                    'ip' => $clientIp,
                ], 403);
            }

            abort(403, 'Access denied. Your IP address is not authorized.');
        }

        return $next($request);
    }
}
