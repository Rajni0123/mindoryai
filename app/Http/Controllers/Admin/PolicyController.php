<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Policy;
use Illuminate\Http\Request;

class PolicyController extends Controller
{
    /**
     * Display a listing of all policies
     */
    public function index()
    {
        $policies = Policy::ordered()->get();
        return view('admin.policies.index', compact('policies'));
    }

    /**
     * Show the form for editing a policy
     */
    public function edit($id)
    {
        $policy = Policy::findOrFail($id);
        return view('admin.policies.edit', compact('policy'));
    }

    /**
     * Update the specified policy
     */
    public function update(Request $request, $id)
    {
        $policy = Policy::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|array',
            'is_enabled' => 'boolean',
            'order' => 'integer',
        ]);

        $policy->update($validated);

        return redirect()
            ->route('admin.policies.index')
            ->with('success', 'Policy updated successfully.');
    }

    /**
     * Toggle policy status (enable/disable)
     */
    public function toggleStatus($id)
    {
        $policy = Policy::findOrFail($id);
        $policy->is_enabled = !$policy->is_enabled;
        $policy->save();

        return redirect()
            ->route('admin.policies.index')
            ->with('success', 'Policy status updated successfully.');
    }

    /**
     * API: Get all policies for mobile app
     */
    public function getPolicies()
    {
        $policies = Policy::enabled()
            ->ordered()
            ->get(['key', 'title', 'content']);

        return response()->json([
            'success' => true,
            'policies' => $policies,
        ]);
    }

    /**
     * API: Get single policy by key
     */
    public function getPolicyByKey($key)
    {
        $policy = Policy::where('key', $key)
            ->where('is_enabled', true)
            ->first();

        if (!$policy) {
            return response()->json([
                'success' => false,
                'message' => 'Policy not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'policy' => $policy,
        ]);
    }
}
