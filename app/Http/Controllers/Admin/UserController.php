<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserPlan;
use App\Models\UserSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserController extends Controller
{
    /**
     * Display users and access codes
     */
    public function index(Request $request)
    {
        $query = User::with(['userCredits', 'plan', 'subscription']);

        // Search
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('mobile', 'like', "%{$search}%");
            });
        }

        // Filter
        $filter = $request->get('filter', 'all');
        if ($filter === 'paid') {
            $query->whereNotNull('plan_id');
        } elseif ($filter === 'free') {
            $query->whereNull('plan_id');
        } elseif ($filter === 'active') {
            $query->where('is_active', true);
        } elseif ($filter === 'inactive') {
            $query->where('is_active', false);
        }

        $users = $query->orderBy('created_at', 'desc')->get();

        // Stats
        $totalUsers = User::count();
        $paidUsers = User::whereNotNull('plan_id')->count();
        $freeUsers = User::whereNull('plan_id')->count();
        $newToday = User::whereDate('created_at', today())->count();

        $stats = [
            'total_users' => $totalUsers,
            'paid_users' => $paidUsers,
            'free_users' => $freeUsers,
            'new_today' => $newToday,
        ];

        // All plans for change-plan dropdown
        $plans = UserPlan::where('is_active', true)->orderBy('order')->get();

        // Access codes
        $accessCodes = DB::table('access_codes')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.users', compact('users', 'accessCodes', 'stats', 'plans'));
    }

    /**
     * Generate a new access code
     */
    public function generateCode(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:access_codes,code|max:255',
            'plan' => 'required|in:basic,premium,lifetime',
            'description' => 'nullable|string|max:500',
        ]);

        DB::table('access_codes')->insert([
            'code' => $validated['code'],
            'plan' => $validated['plan'],
            'description' => $validated['description'] ?? null,
            'is_used' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('admin.users')
            ->with('success', 'Access code generated successfully!');
    }

    /**
     * Delete an access code
     */
    public function destroy($code)
    {
        DB::table('access_codes')->where('code', $code)->delete();

        return redirect()->route('admin.users')
            ->with('success', 'Access code deleted successfully!');
    }

    /**
     * Show the form for creating a new user
     */
    public function create()
    {
        $plans = \App\Models\UserPlan::all();
        return view('admin.users.create', compact('plans'));
    }

    /**
     * Store a newly created user
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|in:user,admin',
            'plan_id' => 'nullable|exists:user_plans,id',
            'is_active' => 'nullable|boolean',
        ]);

        $userData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'role' => $validated['role'],
            'is_active' => $request->has('is_active') ? 1 : 0,
            'tokens_used' => 0,
        ];

        // If plan selected, apply plan's AI access flags
        if (!empty($validated['plan_id'])) {
            $plan = UserPlan::findOrFail($validated['plan_id']);
            $userData['plan_id'] = $plan->id;
            $userData['can_use_gpt4'] = $plan->can_use_gpt4;
            $userData['can_use_claude'] = $plan->can_use_claude;
            $userData['can_use_deepseek'] = $plan->can_use_deepseek;
            $userData['can_use_grok'] = $plan->can_use_grok;
        }

        $user = User::create($userData);

        // Setup subscription and credits if plan assigned
        if (!empty($validated['plan_id'])) {
            UserSubscription::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'status' => 'active',
                'start_date' => now(),
                'end_date' => $plan->validity_days ? now()->addDays($plan->validity_days) : null,
                'credits_included' => $plan->message_tokens,
            ]);

            DB::table('user_credits')->insert([
                'user_id' => $user->id,
                'available_credits' => $plan->unlimited_credits ? 0 : ($plan->message_tokens ?? 50),
                'unlimited_mode' => $plan->unlimited_credits ?? false,
                'total_earned' => $plan->message_tokens ?? 50,
                'total_spent' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return redirect()->route('admin.users')
            ->with('success', 'User created successfully!');
    }

    /**
     * Show the form for editing a user
     */
    public function edit(User $user)
    {
        $plans = \App\Models\UserPlan::all();
        return view('admin.users.edit', compact('user', 'plans'));
    }

    /**
     * Change user's plan (AJAX)
     */
    public function changePlan(Request $request, User $user)
    {
        $request->validate([
            'plan_id' => 'nullable|exists:user_plans,id',
        ]);

        $planId = $request->plan_id;

        if ($planId) {
            $plan = UserPlan::findOrFail($planId);

            // Update user's plan
            $user->update([
                'plan_id' => $plan->id,
                'can_use_gpt4' => $plan->can_use_gpt4,
                'can_use_claude' => $plan->can_use_claude,
                'can_use_deepseek' => $plan->can_use_deepseek,
                'can_use_grok' => $plan->can_use_grok,
            ]);

            // Create/update subscription
            UserSubscription::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'plan_id' => $plan->id,
                    'status' => 'active',
                    'start_date' => now(),
                    'end_date' => $plan->validity_days ? now()->addDays($plan->validity_days) : null,
                    'credits_included' => $plan->message_tokens,
                ]
            );

            // Update credits
            DB::table('user_credits')->updateOrInsert(
                ['user_id' => $user->id],
                [
                    'available_credits' => $plan->unlimited_credits ? 0 : ($plan->message_tokens ?? 50),
                    'unlimited_mode' => $plan->unlimited_credits ?? false,
                    'updated_at' => now(),
                ]
            );

            $planName = $plan->name;
        } else {
            // Set to free (remove plan)
            $user->update(['plan_id' => null]);

            // Cancel subscription
            UserSubscription::where('user_id', $user->id)->update(['status' => 'cancelled']);

            // Reset credits to free tier
            DB::table('user_credits')->updateOrInsert(
                ['user_id' => $user->id],
                [
                    'available_credits' => 50,
                    'unlimited_mode' => false,
                    'updated_at' => now(),
                ]
            );

            $planName = 'Free';
        }

        return response()->json([
            'success' => true,
            'message' => "User plan changed to {$planName}",
            'plan_name' => $planName,
            'is_paid' => $planId !== null,
        ]);
    }

    /**
     * Update the specified user
     */
    public function updateUser(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'plan_id' => 'nullable|exists:user_plans,id',
            'is_active' => 'nullable|boolean',
        ]);

        // Update user basic info
        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'is_active' => $request->has('is_active'),
        ]);

        // Handle plan change (plan controls credits + AI access)
        $newPlanId = $validated['plan_id'] ?? null;
        if ($newPlanId != $user->plan_id) {
            if ($newPlanId) {
                $plan = UserPlan::findOrFail($newPlanId);
                $user->update([
                    'plan_id' => $plan->id,
                    'can_use_gpt4' => $plan->can_use_gpt4,
                    'can_use_claude' => $plan->can_use_claude,
                    'can_use_deepseek' => $plan->can_use_deepseek,
                    'can_use_grok' => $plan->can_use_grok,
                ]);

                UserSubscription::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'plan_id' => $plan->id,
                        'status' => 'active',
                        'start_date' => now(),
                        'end_date' => $plan->validity_days ? now()->addDays($plan->validity_days) : null,
                        'credits_included' => $plan->message_tokens,
                    ]
                );

                DB::table('user_credits')->updateOrInsert(
                    ['user_id' => $user->id],
                    [
                        'available_credits' => $plan->unlimited_credits ? 0 : ($plan->message_tokens ?? 50),
                        'unlimited_mode' => $plan->unlimited_credits ?? false,
                        'updated_at' => now(),
                    ]
                );
            } else {
                $user->update([
                    'plan_id' => null,
                    'can_use_gpt4' => false,
                    'can_use_claude' => false,
                    'can_use_deepseek' => false,
                    'can_use_grok' => false,
                ]);
                UserSubscription::where('user_id', $user->id)->update(['status' => 'cancelled']);
                DB::table('user_credits')->updateOrInsert(
                    ['user_id' => $user->id],
                    ['available_credits' => 50, 'unlimited_mode' => false, 'updated_at' => now()]
                );
            }
        }

        return redirect()->route('admin.users')
            ->with('success', 'User updated successfully!');
    }

    /**
     * Reset user's token usage
     */
    public function resetTokens(User $user)
    {
        $user->update([
            'tokens_used' => 0,
            'last_token_reset' => now(),
        ]);

        return redirect()->route('admin.users')
            ->with('success', 'User tokens reset successfully!');
    }

    /**
     * Add tokens to user's limit
     */
    public function addTokens(Request $request, User $user)
    {
        $validated = $request->validate([
            'tokens_to_add' => 'required|integer|min:1',
        ]);

        $user->update([
            'token_limit' => $user->token_limit + $validated['tokens_to_add'],
        ]);

        return redirect()->back()
            ->with('success', "{$validated['tokens_to_add']} tokens added successfully!");
    }

    /**
     * Toggle user's active status
     */
    public function toggleActive(User $user)
    {
        $user->update([
            'is_active' => !$user->is_active,
        ]);

        $status = $user->is_active ? 'activated' : 'blocked';
        return redirect()->route('admin.users')
            ->with('success', "User {$status} successfully!");
    }

    /**
     * Delete a user
     */
    public function deleteUser(User $user)
    {
        // Prevent deleting current user
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users')
                ->with('error', 'You cannot delete your own account!');
        }

        // Clean up all related records before deleting (prevents SQLite cascade errors)
        DB::statement('PRAGMA foreign_keys = OFF');

        DB::table('faqs')->where('user_id', $user->id)->delete();
        DB::table('study_guides')->where('user_id', $user->id)->delete();
        DB::table('audio_overviews')->where('user_id', $user->id)->delete();
        DB::table('quizzes')->where('user_id', $user->id)->delete();
        DB::table('quiz_attempts')->where('user_id', $user->id)->delete();
        DB::table('user_credits')->where('user_id', $user->id)->delete();
        DB::table('user_subscriptions')->where('user_id', $user->id)->delete();
        DB::table('conversations')->where('user_id', $user->id)->delete();
        DB::table('messages')->where('user_id', $user->id)->delete();
        DB::table('mobile_chats')->where('user_id', $user->id)->delete();
        DB::table('mobile_chat_messages')->whereIn('chat_id', DB::table('mobile_chats')->where('user_id', $user->id)->pluck('id'))->delete();
        DB::table('personal_access_tokens')->where('tokenable_id', $user->id)->where('tokenable_type', 'App\\Models\\User')->delete();
        DB::table('whiteboard_videos')->where('user_id', $user->id)->delete();
        DB::table('image_analyses')->where('user_id', $user->id)->delete();
        DB::table('ai_usage_tracking')->where('user_id', $user->id)->delete();
        DB::table('credit_transactions')->where('user_id', $user->id)->delete();

        $user->delete();

        DB::statement('PRAGMA foreign_keys = ON');

        return redirect()->route('admin.users')
            ->with('success', 'User deleted successfully!');
    }
}
