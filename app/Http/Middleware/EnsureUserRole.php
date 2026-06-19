<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    /**
     * Handle an incoming request.
     *
     * User role middleware - ensures only regular users can access
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is not authenticated
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Please login to continue');
        }

        $user = auth()->user();

        // If user is admin, redirect to admin dashboard
        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        // If user role is not 'user', redirect to login
        if (!$user->isUser()) {
            return redirect()->route('login')->with('error', 'Unauthorized access');
        }

        return $next($request);
    }
}
