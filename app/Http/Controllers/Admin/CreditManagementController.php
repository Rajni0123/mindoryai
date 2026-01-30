<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Services\CreditService;

/**
 * Admin Panel - Credit & Feature Management
 *
 * Provides admin interface to:
 * - View and manage feature flags
 * - Adjust user credits
 * - View credit transactions
 * - Configure daily limits
 * - Monitor usage statistics
 */
class CreditManagementController extends Controller
{
    protected $creditService;

    public function __construct(CreditService $creditService)
    {
        $this->middleware(['auth', 'admin']);
        $this->creditService = $creditService;
    }

    /**
     * Show credit management dashboard
     */
    public function index()
    {
        // Get feature flags
        $featureFlags = DB::table('feature_flags')
            ->orderBy('feature_name')
            ->get();

        // Get system statistics
        $totalUsers = User::count();
        $totalCreditsIssued = DB::table('user_credits')->sum('total_earned');
        $totalCreditsSpent = DB::table('user_credits')->sum('total_spent');
        $usersWithUnlimited = DB::table('user_credits')->where('unlimited_mode', true)->count();

        // Get recent transactions
        $recentTransactions = DB::table('credit_transactions')
            ->join('users', 'credit_transactions.user_id', '=', 'users.id')
            ->select(
                'credit_transactions.*',
                'users.name as user_name',
                'users.email as user_email'
            )
            ->orderBy('credit_transactions.created_at', 'desc')
            ->limit(20)
            ->get();

        // Get top credit consumers
        $topConsumers = DB::table('user_credits')
            ->join('users', 'user_credits.user_id', '=', 'users.id')
            ->select(
                'users.id',
                'users.name',
                'users.email',
                'user_credits.available_credits',
                'user_credits.total_earned',
                'user_credits.total_spent',
                'user_credits.unlimited_mode'
            )
            ->orderBy('user_credits.total_spent', 'desc')
            ->limit(10)
            ->get();

        return view('admin.credit-management', compact(
            'featureFlags',
            'totalUsers',
            'totalCreditsIssued',
            'totalCreditsSpent',
            'usersWithUnlimited',
            'recentTransactions',
            'topConsumers'
        ));
    }

    /**
     * Update feature flag
     */
    public function updateFeatureFlag(Request $request, $id)
    {
        $validated = $request->validate([
            'is_enabled' => 'required|boolean',
            'config' => 'nullable|json',
        ]);

        DB::table('feature_flags')
            ->where('id', $id)
            ->update([
                'is_enabled' => $validated['is_enabled'],
                'config' => $validated['config'] ?? null,
                'updated_at' => now(),
            ]);

        // Clear cache
        cache()->forget('feature_flag.' . $request->input('feature_key'));

        return redirect()
            ->back()
            ->with('success', 'Feature flag updated successfully');
    }

    /**
     * Update feature flag configuration (AJAX)
     */
    public function updateFeatureFlagAjax(Request $request, $id)
    {
        $validated = $request->validate([
            'is_enabled' => 'sometimes|boolean',
            'config' => 'sometimes|array',
        ]);

        $updates = ['updated_at' => now()];

        if (isset($validated['is_enabled'])) {
            $updates['is_enabled'] = $validated['is_enabled'];
        }

        if (isset($validated['config'])) {
            $updates['config'] = json_encode($validated['config']);
        }

        $flag = DB::table('feature_flags')->where('id', $id)->first();

        DB::table('feature_flags')
            ->where('id', $id)
            ->update($updates);

        // Clear cache
        if ($flag) {
            cache()->forget('feature_flag.' . $flag->feature_key);
        }

        return response()->json([
            'success' => true,
            'message' => 'Feature updated successfully',
        ]);
    }

    /**
     * Adjust user credits
     */
    public function adjustUserCredits(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|integer',
            'reason' => 'required|string|max:100',
            'description' => 'nullable|string',
        ]);

        $admin = auth()->user();
        $user = User::findOrFail($validated['user_id']);
        $amount = $validated['amount'];

        // Enhanced description with admin info
        $adminDescription = "Admin adjustment by {$admin->name}. Reason: {$validated['reason']}";
        if ($validated['description']) {
            $adminDescription .= " - {$validated['description']}";
        }

        if ($amount > 0) {
            // Add credits
            $result = $this->creditService->addCredits(
                $user,
                $amount,
                'admin_adjustment',
                $adminDescription
            );
        } else {
            // Deduct credits (negative amount)
            $result = $this->creditService->deductCredits(
                $user,
                'admin_adjustment',
                abs($amount),
                $adminDescription
            );
        }

        // Log admin action for audit
        \Log::info('Admin credit adjustment', [
            'admin_id' => $admin->id,
            'admin_name' => $admin->name,
            'target_user_id' => $user->id,
            'target_user_email' => $user->email,
            'amount' => $amount,
            'reason' => $validated['reason'],
            'success' => $result['success'],
            'ip' => $request->ip(),
        ]);

        if ($result['success']) {
            return redirect()
                ->back()
                ->with('success', "Credits adjusted successfully. New balance: {$result['new_balance']}");
        } else {
            return redirect()
                ->back()
                ->with('error', $result['message']);
        }
    }

    /**
     * Toggle unlimited mode for user
     */
    public function toggleUnlimitedMode(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'unlimited_mode' => 'required|boolean',
            'unlimited_until' => 'nullable|date',
            'reason' => 'required|string|max:255',
        ]);

        $admin = auth()->user();
        $targetUser = User::findOrFail($validated['user_id']);

        // Initialize user credits if not exists
        $this->creditService->initializeUserCredits($targetUser);

        // Get current balance before update
        $currentBalance = DB::table('user_credits')
            ->where('user_id', $validated['user_id'])
            ->value('available_credits');

        DB::beginTransaction();
        try {
            // Update unlimited mode
            DB::table('user_credits')
                ->where('user_id', $validated['user_id'])
                ->update([
                    'unlimited_mode' => $validated['unlimited_mode'],
                    'unlimited_until' => $validated['unlimited_until'] ?? null,
                    'updated_at' => now(),
                ]);

            // Create audit transaction record
            $status = $validated['unlimited_mode'] ? 'enabled' : 'disabled';
            $description = "Unlimited mode {$status} by admin {$admin->name}. Reason: {$validated['reason']}";

            if ($validated['unlimited_until']) {
                $description .= " Until: {$validated['unlimited_until']}";
            }

            DB::table('credit_transactions')->insert([
                'user_id' => $validated['user_id'],
                'type' => 'admin_adjustment',
                'amount' => 0, // No credit change, just mode change
                'balance_after' => $currentBalance,
                'reason' => 'unlimited_mode_change',
                'description' => $description,
                'reference_type' => 'admin_action',
                'reference_id' => $admin->id,
                'ip_address' => $request->ip(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            // Log the action
            \Log::info('Admin unlimited mode change', [
                'admin_id' => $admin->id,
                'admin_name' => $admin->name,
                'target_user_id' => $targetUser->id,
                'target_user_email' => $targetUser->email,
                'unlimited_mode' => $validated['unlimited_mode'],
                'unlimited_until' => $validated['unlimited_until'],
                'reason' => $validated['reason'],
                'ip' => $request->ip(),
            ]);

            return redirect()
                ->back()
                ->with('success', "Unlimited mode {$status} for user. Audit trail created.");
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to toggle unlimited mode', [
                'admin_id' => $admin->id,
                'target_user_id' => $targetUser->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->back()
                ->with('error', 'Failed to update unlimited mode: ' . $e->getMessage());
        }
    }

    /**
     * View user credit details
     */
    public function showUserCredits($userId)
    {
        $user = User::findOrFail($userId);

        // Initialize credits if needed
        $this->creditService->initializeUserCredits($user);

        // Get credit stats
        $stats = $this->creditService->getUserStats($user);

        // Get transactions
        $transactions = DB::table('credit_transactions')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();

        // Get daily usage
        $dailyUsage = DB::table('daily_usage_limits')
            ->where('user_id', $userId)
            ->orderBy('usage_date', 'desc')
            ->limit(30)
            ->get();

        return view('admin.user-credits', compact(
            'user',
            'stats',
            'transactions',
            'dailyUsage'
        ));
    }

    /**
     * Search users for credit adjustment
     */
    public function searchUsers(Request $request)
    {
        $query = $request->input('q');

        $users = User::where('name', 'like', "%{$query}%")
            ->orWhere('email', 'like', "%{$query}%")
            ->orWhere('mobile', 'like', "%{$query}%")
            ->limit(10)
            ->get(['id', 'name', 'email', 'mobile']);

        return response()->json($users);
    }

    /**
     * Export credit transactions
     */
    public function exportTransactions(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $query = DB::table('credit_transactions')
            ->join('users', 'credit_transactions.user_id', '=', 'users.id')
            ->select(
                'credit_transactions.*',
                'users.name as user_name',
                'users.email as user_email'
            );

        if ($request->filled('start_date')) {
            $query->where('credit_transactions.created_at', '>=', $validated['start_date']);
        }

        if ($request->filled('end_date')) {
            $query->where('credit_transactions.created_at', '<=', $validated['end_date']);
        }

        if ($request->filled('user_id')) {
            $query->where('credit_transactions.user_id', $validated['user_id']);
        }

        $transactions = $query->orderBy('credit_transactions.created_at', 'desc')->get();

        // Generate CSV
        $filename = 'credit_transactions_' . now()->format('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($transactions) {
            $file = fopen('php://output', 'w');

            // Header row
            fputcsv($file, [
                'ID',
                'User Name',
                'User Email',
                'Type',
                'Amount',
                'Balance After',
                'Reason',
                'Description',
                'IP Address',
                'Date',
            ]);

            // Data rows
            foreach ($transactions as $transaction) {
                fputcsv($file, [
                    $transaction->id,
                    $transaction->user_name,
                    $transaction->user_email,
                    $transaction->type,
                    $transaction->amount,
                    $transaction->balance_after,
                    $transaction->reason,
                    $transaction->description,
                    $transaction->ip_address,
                    $transaction->created_at,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
