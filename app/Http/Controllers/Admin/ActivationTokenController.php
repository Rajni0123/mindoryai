<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserActivationToken;
use App\Models\UserPlan;
use Illuminate\Http\Request;

class ActivationTokenController extends Controller
{
    /**
     * Display all activation tokens
     */
    public function index()
    {
        $tokens = UserActivationToken::with(['user', 'plan'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $plans = UserPlan::where('is_active', true)->get();

        return view('admin.tokens.index', compact('tokens', 'plans'));
    }

    /**
     * Generate a new activation token
     */
    public function generate(Request $request)
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:user_plans,id',
            'expiry_days' => 'nullable|integer|min:1',
        ]);

        $token = UserActivationToken::generateToken(
            $validated['plan_id'],
            $validated['expiry_days'] ?? null
        );

        return redirect()->route('admin.tokens.index')
            ->with('success', 'Activation token generated successfully!')
            ->with('token', $token->raw_token);
    }

    /**
     * Deactivate a token
     */
    public function deactivate(UserActivationToken $token)
    {
        $token->update(['is_active' => false]);

        return redirect()->route('admin.tokens.index')
            ->with('success', 'Token deactivated successfully!');
    }

    /**
     * Delete a token
     */
    public function destroy(UserActivationToken $token)
    {
        $token->delete();

        return redirect()->route('admin.tokens.index')
            ->with('success', 'Token deleted successfully!');
    }
}
