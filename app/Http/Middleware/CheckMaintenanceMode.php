<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    /**
     * Handle an incoming request.
     *
     * Check if maintenance mode is enabled from database settings
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get maintenance mode setting from database
        $maintenanceMode = \App\Models\Setting::get('maintenance_mode', '0');

        // If maintenance mode is enabled
        if ($maintenanceMode === '1' || $maintenanceMode === 1 || $maintenanceMode === true) {
            // Allow admin users to access the site
            if (auth()->check() && auth()->user()->isAdmin()) {
                return $next($request);
            }

            // Exclude admin routes, login routes, and API health checks
            $excludedPaths = [
                'admin/*',
                'admin/login',
                'login',
                'admin-login',
                'logout',
                'up',
                'google/*',
            ];

            foreach ($excludedPaths as $path) {
                if ($request->is($path)) {
                    return $next($request);
                }
            }

            // Show maintenance page
            return response()->view('errors.maintenance', [
                'message' => \App\Models\Setting::get('maintenance_message', 'We are currently performing scheduled maintenance. Please check back soon.')
            ], 503);
        }

        return $next($request);
    }
}
