<?php

namespace App\Http\Middleware;

use App\Services\FeatureLimitService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckFeatureLimit
{
    public function __construct(
        protected FeatureLimitService $featureLimitService
    ) {}

    /**
     * Handle an incoming request.
     *
     * Usage: ->middleware('check.feature:ai_doubt')
     * Usage: ->middleware('check.feature:scan_solve')
     * Usage: ->middleware('check.feature:whiteboard_video')
     * Usage: ->middleware('check.feature:quiz')
     * Usage: ->middleware('check.feature:topic_quiz_gen')
     * Usage: ->middleware('check.feature:reasoning')
     * Usage: ->middleware('check.feature:student_chat')
     * Usage: ->middleware('check.feature:notes_diagrams')
     *
     * @param string|null $featureSlug The feature slug to check
     */
    public function handle(Request $request, Closure $next, ?string $featureSlug = null): Response
    {
        if (!$featureSlug) {
            return $next($request);
        }

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'error' => 'unauthenticated',
                'message' => 'Please login to access this feature.',
            ], 401);
        }

        // Admins bypass all limits
        if ($user->role === 'admin') {
            return $next($request);
        }

        // Check if user can use this feature
        if (!$this->featureLimitService->canUse($user, $featureSlug)) {
            $response = $this->featureLimitService->getLimitExceededResponse($user, $featureSlug);
            return response()->json($response, 403);
        }

        // Process the request
        $response = $next($request);

        // If request was successful, track the usage (never fail the API response if tracking breaks)
        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
            try {
                $this->featureLimitService->trackUsage($user, $featureSlug);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Feature usage tracking failed', [
                    'feature' => $featureSlug,
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $response;
    }
}
