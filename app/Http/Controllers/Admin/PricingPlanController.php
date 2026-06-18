<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PricingPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PricingPlanController extends Controller
{
    public function index()
    {
        $plans = PricingPlan::orderBy('order')->get();
        return view('admin.pricing.index', compact('plans'));
    }

    public function create()
    {
        return view('admin.pricing.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'billing_period' => 'required|string|in:month,year',
            'billing_description' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'order' => 'required|integer|min:0',
            'validity_days' => 'nullable|integer|min:0',
        ]);

        $features = $this->buildFeaturesJson($request);

        PricingPlan::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
            'price' => $request->price,
            'billing_period' => $request->billing_period,
            'billing_description' => $request->billing_description,
            'features' => $features,
            'order' => $request->order,
            'validity_days' => $request->validity_days ?? 30,
            'is_active' => $request->has('is_active'),
            'unlimited_credits' => $request->has('unlimited_credits'),
            'message_tokens' => $request->message_tokens ?? 0,
            'can_use_gpt4' => $request->has('can_use_gpt4'),
            'can_use_claude' => $request->has('can_use_claude'),
            'can_use_deepseek' => $request->has('can_use_deepseek'),
            'can_use_grok' => $request->has('can_use_grok'),
        ]);

        return redirect()->route('admin.pricing.index')
            ->with('success', 'Plan created successfully!');
    }

    public function edit(PricingPlan $pricingPlan)
    {
        $plan = $pricingPlan;
        return view('admin.pricing.edit', compact('plan'));
    }

    public function update(Request $request, PricingPlan $pricingPlan)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'billing_period' => 'required|string|in:month,year',
            'billing_description' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'order' => 'required|integer|min:0',
            'validity_days' => 'nullable|integer|min:0',
        ]);

        $features = $this->buildFeaturesJson($request);

        $pricingPlan->update([
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'billing_period' => $request->billing_period,
            'billing_description' => $request->billing_description,
            'features' => $features,
            'order' => $request->order,
            'validity_days' => $request->validity_days ?? 30,
            'is_active' => $request->has('is_active'),
            'unlimited_credits' => $request->has('unlimited_credits'),
            'message_tokens' => $request->message_tokens ?? 0,
            'can_use_gpt4' => $request->has('can_use_gpt4'),
            'can_use_claude' => $request->has('can_use_claude'),
            'can_use_deepseek' => $request->has('can_use_deepseek'),
            'can_use_grok' => $request->has('can_use_grok'),
        ]);

        return redirect()->route('admin.pricing.index')
            ->with('success', 'Plan updated successfully!');
    }

    public function destroy(PricingPlan $pricingPlan)
    {
        $pricingPlan->delete();

        return redirect()->route('admin.pricing.index')
            ->with('success', 'Plan deleted successfully!');
    }

    /**
     * Build the features JSON from all form sections
     */
    private function buildFeaturesJson(Request $request): array
    {
        // Section 1: Display features (text list for public pages)
        $displayFeatures = array_filter($request->input('display_features', []), fn($f) => !empty(trim($f)));

        // Section 2: Usage limits (dynamic key-value pairs)
        $limitKeys = $request->input('limit_keys', []);
        $limitValues = $request->input('limit_values', []);
        $dailyLimits = [];
        foreach ($limitKeys as $i => $key) {
            $key = trim($key);
            if (!empty($key) && isset($limitValues[$i])) {
                $dailyLimits[$key] = (int) $limitValues[$i];
            }
        }

        // Section 3: Plan config
        $planConfig = [
            'max_video_length_seconds' => (int) $request->input('max_video_length_seconds', 30),
            'frames_per_video' => (int) $request->input('frames_per_video', 5),
            'pages_per_pdf' => (int) $request->input('pages_per_pdf', 5),
            'history_days' => (int) $request->input('history_days', 3),
            'watermark' => $request->has('watermark'),
            'ads' => $request->has('ads'),
            'priority_queue' => $request->has('priority_queue'),
        ];

        // Section 4: Flags
        $flags = [
            'popular' => $request->has('popular'),
            'recommended' => $request->has('recommended'),
        ];

        return array_merge($planConfig, $flags, [
            'features' => array_values($displayFeatures),
            'features_list' => array_values($displayFeatures),
            'daily_limits' => $dailyLimits,
        ]);
    }
}
