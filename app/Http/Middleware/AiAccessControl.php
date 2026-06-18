<?php

namespace App\Http\Middleware;

use App\Models\UserIpWhitelist;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AiAccessControl
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // User must be authenticated
        if (!$user) {
            return $this->denyAccess($request, 'You must be logged in to access AI features.');
        }

        // ADMINS HAVE UNLIMITED ACCESS - Skip all checks
        if ($user->role === 'admin') {
            return $next($request);
        }

        // Check if paid plan has expired - auto-downgrade to free
        if ($user->plan_id && $user->plan_expires_at && now()->gt($user->plan_expires_at)) {
            $user->update([
                'plan_id' => null,
                'plan_expires_at' => null,
            ]);
            $user->refresh();
        }

        // Check if user account is active
        if (!$user->is_active) {
            return $this->denyAccess($request, 'Your account has been deactivated. Please contact support.');
        }

        // Free users (plan_id = null) are allowed - they get free plan limits
        // via UsageLimitService. Individual controllers enforce daily limits.

        // Check IP whitelist (if user has whitelist entries)
        $userIps = $user->ipWhitelist()->where('is_active', true)->count();
        if ($userIps > 0) {
            $clientIp = $request->ip();
            if (!$user->isIpWhitelisted($clientIp)) {
                return $this->denyAccess($request, 'Access denied. Your IP address (' . $clientIp . ') is not whitelisted for this account.');
            }
        }

        return $next($request);
    }

    /**
     * Deny access with appropriate response
     */
    private function denyAccess(Request $request, string $message): Response
    {
        // Always return JSON response for API endpoints
        // Never redirect - let frontend handle the user experience
        return response()->json([
            'success' => false,
            'message' => $message,
        ], 403);
    }
}
