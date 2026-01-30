<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PricingPlan;
use Illuminate\Http\Request;

class PricingPlanController extends Controller
{
    /**
     * Display a listing of the pricing plans.
     */
    public function index()
    {
        $plans = PricingPlan::orderBy('order')->get();
        return view('admin.pricing.index', compact('plans'));
    }

    /**
     * Show the form for creating a new pricing plan.
     */
    public function create()
    {
        return view('admin.pricing.create');
    }

    /**
     * Store a newly created pricing plan in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'period' => 'required|string',
            'features' => 'required|array',
            'features.*' => 'required|string',
            'badge' => 'nullable|string|max:255',
            'badge_color' => 'required|string',
            'button_text' => 'required|string|max:255',
            'order' => 'required|integer|min:0',
            'is_active' => 'boolean',
            'is_highlighted' => 'boolean',
            'is_recommended' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['is_highlighted'] = $request->has('is_highlighted');
        $isRecommended = $request->has('is_recommended');

        // Wrap features array in the expected JSON format
        $validated['features'] = json_encode([
            'features' => $validated['features'],
            'recommended' => $isRecommended,
        ]);

        PricingPlan::create($validated);

        return redirect()->route('admin.pricing.index')
            ->with('success', 'Pricing plan created successfully!');
    }

    /**
     * Show the form for editing the specified pricing plan.
     */
    public function edit(PricingPlan $pricingPlan)
    {
        $plan = $pricingPlan;
        return view('admin.pricing.edit', compact('plan'));
    }

    /**
     * Update the specified pricing plan in storage.
     */
    public function update(Request $request, PricingPlan $pricingPlan)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'period' => 'required|string',
            'features' => 'required|array',
            'features.*' => 'required|string',
            'badge' => 'nullable|string|max:255',
            'badge_color' => 'required|string',
            'button_text' => 'required|string|max:255',
            'order' => 'required|integer|min:0',
            'is_active' => 'boolean',
            'is_highlighted' => 'boolean',
            'is_recommended' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['is_highlighted'] = $request->has('is_highlighted');
        $isRecommended = $request->has('is_recommended');

        // Wrap features array in the expected JSON format, using checkbox value
        $validated['features'] = json_encode([
            'features' => $validated['features'],
            'recommended' => $isRecommended,
        ]);

        $pricingPlan->update($validated);

        return redirect()->route('admin.pricing.index')
            ->with('success', 'Pricing plan updated successfully!');
    }

    /**
     * Remove the specified pricing plan from storage.
     */
    public function destroy(PricingPlan $pricingPlan)
    {
        $pricingPlan->delete();

        return redirect()->route('admin.pricing.index')
            ->with('success', 'Pricing plan deleted successfully!');
    }
}
