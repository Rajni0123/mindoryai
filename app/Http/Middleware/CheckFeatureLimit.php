<?php

namespace App\Http\Middleware;

use App\Services\UsageLimitService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckFeatureLimit
{
    protected UsageLimitService $usageService;

    public function __construct(UsageLimitService $usageService)
    {
        $this->usageService = $usageService;
    }

    /**
     * Handle an incoming request.
     *
     * Usage: ->middleware('check.feature:video_quiz')
     * Usage: ->middleware('check.feature:whiteboard_video')
     * Usage: ->middleware('check.feature:topic_quiz')
     * Usage: ->middleware('check.feature:exam_prep')
     * Usage: ->middleware('check.feature:scan_solve')
     * Usage: ->middleware('check.feature:pdf_upload')
     *
     * @param string|null $featureType The feature key
     */
    public function handle(Request $request, Closure $next, ?string $featureType = null): Response
    {
        if (!$featureType) {
            return $next($request);
        }

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required.',
            ], 401);
        }

        // Admins bypass all limits
        if ($user->role === 'admin') {
            return $next($request);
        }

        // checkAndRecord: checks limit AND records usage in one call
        $check = $this->usageService->checkAndRecord($user, $featureType);

        if (!$check['allowed']) {
            return response()->json([
                'success' => false,
                'message' => $check['reason'],
                'limit' => $check['limit'],
                'used' => $check['used'],
                'remaining' => $check['remaining'],
                'upgrade_required' => true,
            ], 429);
        }

        return $next($request);
    }
}
