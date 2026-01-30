<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckSubscriptionActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        // If user is not logged in, redirect to login
        if (!$user) {
            return redirect()->route('login');
        }

        // Admin users can always access
        if ($user->isAdmin()) {
            return $next($request);
        }

        // Refresh user data from database to get latest values
        $user->refresh();

        // Check if user has an active subscription
        // Allow access to chat page but show warning message instead of redirecting
        if (!$user->is_active || !$user->plan_id) {
            // Let them access the chat page, frontend will handle the warning
            return $next($request);
        }

        return $next($request);
    }
}
