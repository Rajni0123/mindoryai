<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminRole
{
    /**
     * Handle an incoming request.
     *
     * Admin role middleware with 404 redirect for unauthorized access
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Debug logging
        \Log::info('EnsureAdminRole Middleware Check', [
            'url' => $request->url(),
            'session_id' => session()->getId(),
            'auth_check' => auth()->check(),
            'auth_id' => auth()->id(),
            'has_session' => $request->hasSession(),
            'session_started' => $request->session()->isStarted(),
        ]);

        // Check if user is not authenticated
        if (!auth()->check()) {
            \Log::warning('Admin middleware: User not authenticated', [
                'url' => $request->url(),
                'session_id' => session()->getId(),
            ]);

            return redirect()->guest('/admin/login');
        }

        $user = auth()->user();

        // Check if user is not admin or account is locked
        if (!$user->isAdmin() || $user->isLocked()) {
            // Show 404 page to hide admin routes from regular users
            return response()->view('errors.404-redirect', [], 404);
        }

        // ⚠️ DISABLED: Custom session expiry check (was causing premature logouts)
        // Laravel's native session handling (SESSION_LIFETIME=120 min) is now used instead
        // if ($user->hasSessionExpired()) {
        //     auth()->logout();
        //     $request->session()->invalidate();
        //     $request->session()->regenerateToken();
        //
        //     return response()->view('errors.404-redirect', ['message' => 'Session expired due to inactivity'], 404);
        // }

        // ⚠️ DISABLED: Last activity tracking (not needed for session management)
        // $user->updateLastActivity();

        return $next($request);
    }
}
