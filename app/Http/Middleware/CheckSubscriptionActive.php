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

        // Check if paid plan has expired - auto-downgrade to free
        if ($user->plan_id && $user->plan_expires_at && now()->gt($user->plan_expires_at)) {
            $user->update([
                'plan_id' => null,
                'plan_expires_at' => null,
            ]);
            $user->refresh();
        }

        return $next($request);
    }
}
