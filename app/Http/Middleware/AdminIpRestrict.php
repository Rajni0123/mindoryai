<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class AdminIpRestrict
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get allowed admin IPs from config or environment
        $allowedIps = $this->getAllowedIps();

        // If no IPs are configured, allow all (for development)
        if (empty($allowedIps)) {
            Log::warning('Admin IP restriction not configured. Allowing all IPs.');
            return $next($request);
        }

        $clientIp = $request->ip();

        // Check if client IP is in allowed list
        if (!in_array($clientIp, $allowedIps)) {
            Log::warning('Admin access denied from unauthorized IP', [
                'ip' => $clientIp,
                'url' => $request->fullUrl(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Access denied. Your IP address is not authorized to access this resource.',
            ], 403);
        }

        return $next($request);
    }

    /**
     * Get list of allowed admin IPs
     */
    protected function getAllowedIps(): array
    {
        // Get from environment variable (comma-separated)
        $ipsFromEnv = env('ADMIN_ALLOWED_IPS', '');

        if (empty($ipsFromEnv)) {
            return [];
        }

        return array_map('trim', explode(',', $ipsFromEnv));
    }
}
