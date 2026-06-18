<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UserPlanController extends Controller
{
    /**
     * Display all user plans
     */
    public function index()
    {
        $plans = UserPlan::orderBy('order')->orderBy('created_at', 'desc')->get();

        return view('admin.pricing.index', compact('plans'));
    }

    /**
     * Show the form for creating a new plan
     */
    public function create()
    {
        return view('admin.pricing.create');
    }

    /**
     * Store a newly created plan
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'billing_period' => 'required|in:month,year',
            'billing_description' => 'nullable|string',
            'message_tokens' => 'required|integer|min:0',
            'image_credits' => 'required|integer|min:0',
            'api_calls' => 'required|integer|min:0',
            'image_uploads' => 'required|integer|min:0',
            'can_use_gpt4' => 'nullable|boolean',
            'can_use_claude' => 'nullable|boolean',
            'can_use_deepseek' => 'nullable|boolean',
            'can_use_grok' => 'nullable|boolean',
            'validity_days' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
            'order' => 'nullable|integer',
            'unlimited_credits' => 'nullable|boolean',
            'features' => 'nullable|array',
            'popular' => 'nullable|boolean',
            'recommended' => 'nullable|boolean',
            // Daily feature limits (-1 = unlimited, 0 = disabled)
            'doubts_per_day' => 'nullable|integer|min:-1',
            'video_solutions_per_day' => 'nullable|integer|min:-1',
            'whiteboard_videos_per_day' => 'nullable|integer|min:-1',
            'quizzes_per_day' => 'nullable|integer|min:-1',
            'scans_per_day' => 'nullable|integer|min:-1',
            'mock_tests_per_month' => 'nullable|integer|min:-1',
            // Plan extras
            'video_quality' => 'nullable|in:sd,hd,4k',
            'device_limit' => 'nullable|integer|min:1|max:10',
            'analytics_tier' => 'nullable|in:basic,standard,advanced',
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['can_use_gpt4'] = $request->has('can_use_gpt4');
        $validated['can_use_claude'] = $request->has('can_use_claude');
        $validated['can_use_deepseek'] = $request->has('can_use_deepseek');
        $validated['can_use_grok'] = $request->has('can_use_grok');
        $validated['is_active'] = $request->has('is_active');
        $validated['unlimited_credits'] = $request->has('unlimited_credits');
        $validated['order'] = $validated['order'] ?? 0;

        // Build features JSON
        $featuresList = $request->input('features.features', []);
        $validated['features'] = $this->buildFeaturesJson($request, $featuresList);

        UserPlan::create($validated);

        return redirect()->route('admin.plans.index')
            ->with('success', 'Plan created successfully!');
    }

    /**
     * Show the form for editing a plan
     */
    public function edit(UserPlan $plan)
    {
        return view('admin.pricing.edit', compact('plan'));
    }

    /**
     * Update a plan
     */
    public function update(Request $request, UserPlan $plan)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'billing_period' => 'required|in:month,year',
            'billing_description' => 'nullable|string',
            'message_tokens' => 'required|integer|min:0',
            'image_credits' => 'required|integer|min:0',
            'api_calls' => 'required|integer|min:0',
            'image_uploads' => 'required|integer|min:0',
            'can_use_gpt4' => 'nullable|boolean',
            'can_use_claude' => 'nullable|boolean',
            'can_use_deepseek' => 'nullable|boolean',
            'can_use_grok' => 'nullable|boolean',
            'validity_days' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
            'order' => 'nullable|integer',
            'unlimited_credits' => 'nullable|boolean',
            'features' => 'nullable|array',
            'popular' => 'nullable|boolean',
            'recommended' => 'nullable|boolean',
            // Daily feature limits (-1 = unlimited, 0 = disabled)
            'doubts_per_day' => 'nullable|integer|min:-1',
            'video_solutions_per_day' => 'nullable|integer|min:-1',
            'whiteboard_videos_per_day' => 'nullable|integer|min:-1',
            'quizzes_per_day' => 'nullable|integer|min:-1',
            'scans_per_day' => 'nullable|integer|min:-1',
            'mock_tests_per_month' => 'nullable|integer|min:-1',
            // Plan extras
            'video_quality' => 'nullable|in:sd,hd,4k',
            'device_limit' => 'nullable|integer|min:1|max:10',
            'analytics_tier' => 'nullable|in:basic,standard,advanced',
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['can_use_gpt4'] = $request->has('can_use_gpt4');
        $validated['can_use_claude'] = $request->has('can_use_claude');
        $validated['can_use_deepseek'] = $request->has('can_use_deepseek');
        $validated['can_use_grok'] = $request->has('can_use_grok');
        $validated['is_active'] = $request->has('is_active');
        $validated['unlimited_credits'] = $request->has('unlimited_credits');
        $validated['order'] = $validated['order'] ?? 0;

        // Build features JSON
        $featuresList = $request->input('features.features', []);
        $validated['features'] = $this->buildFeaturesJson($request, $featuresList);

        $plan->update($validated);

        return redirect()->route('admin.plans.index')
            ->with('success', 'Plan updated successfully!');
    }

    /**
     * Build the features JSON structure from the request.
     */
    private function buildFeaturesJson(Request $request, array $featuresList): array
    {
        return [
            'popular' => $request->has('popular'),
            'recommended' => $request->has('recommended'),
            'features' => $featuresList,
            'daily_limits' => [
                'doubts_per_day' => (int) $request->input('doubts_per_day', 0),
                'video_solutions_per_day' => (int) $request->input('video_solutions_per_day', 0),
                'whiteboard_videos_per_day' => (int) $request->input('whiteboard_videos_per_day', 0),
                'quizzes_per_day' => (int) $request->input('quizzes_per_day', 0),
                'scans_per_day' => (int) $request->input('scans_per_day', 0),
                'mock_tests_per_month' => (int) $request->input('mock_tests_per_month', 0),
            ],
            'video_quality' => $request->input('video_quality', 'sd'),
            'device_limit' => (int) $request->input('device_limit', 1),
            'analytics_tier' => $request->input('analytics_tier', 'basic'),
        ];
    }

    /**
     * Delete a plan
     */
    public function destroy(UserPlan $plan)
    {
        // Check if any tokens use this plan
        if ($plan->activationTokens()->count() > 0) {
            return redirect()->route('admin.plans.index')
                ->with('error', 'Cannot delete plan with existing activation tokens!');
        }

        $plan->delete();

        return redirect()->route('admin.plans.index')
            ->with('success', 'Plan deleted successfully!');
    }
}
