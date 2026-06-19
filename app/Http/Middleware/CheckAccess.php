<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class CheckAccess
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user has access
        if (!Session::has('access_granted') || Session::get('access_granted') !== true) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Please enter a valid access code.',
                ], 403);
            }

            return redirect()->route('verify.access')
                ->with('error', 'Please enter your access code to continue.');
        }

        return $next($request);
    }
}
