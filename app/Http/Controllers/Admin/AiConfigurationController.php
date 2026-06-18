<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiSystemPrompt;
use App\Models\AiUsageTracking;
use App\Models\AiModel;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AiConfigurationController extends Controller
{
    /**
     * Display system prompts management
     */
    public function prompts()
    {
        $prompts = AiSystemPrompt::orderBy('feature')->get();
        return view('admin.ai-configuration.prompts', compact('prompts'));
    }

    /**
     * Update system prompt
     */
    public function updatePrompt(Request $request, AiSystemPrompt $prompt)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'prompt' => 'required|string',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $prompt->update($validated);

        // Clear cache
        AiSystemPrompt::clearCache();

        return redirect()->route('admin.ai-config.prompts')
            ->with('success', 'System prompt updated successfully');
    }

    /**
     * Get real-time usage data as JSON (for AJAX refresh)
     */
    public function usageData(Request $request)
    {
        $days = $request->input('days', 30);

        // Feature-wise statistics
        $featureStats = [];
        $features = ['chat', 'quiz', 'whiteboard', 'image_generation'];

        foreach ($features as $feat) {
            $featureStats[$feat] = AiUsageTracking::getFeatureStats($feat, $days);
        }

        // Daily usage chart data
        $dailyUsage = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dayStats = AiUsageTracking::whereDate('created_at', $date)
                ->selectRaw('
                    SUM(total_tokens) as tokens,
                    SUM(estimated_cost) as cost
                ')
                ->first();

            $dailyUsage[] = [
                'date' => Carbon::parse($date)->format('M d'),
                'tokens' => $dayStats->tokens ?? 0,
                'cost' => $dayStats->cost ?? 0,
            ];
        }

        // Overall statistics
        $overallStats = AiUsageTracking::where('created_at', '>=', now()->subDays($days))
            ->selectRaw('
                COUNT(*) as total_requests,
                SUM(input_tokens) as total_input_tokens,
                SUM(output_tokens) as total_output_tokens,
                SUM(total_tokens) as total_tokens,
                SUM(estimated_cost) as total_cost
            ')
            ->first();

        return response()->json([
            'featureStats' => $featureStats,
            'dailyUsage' => $dailyUsage,
            'overallStats' => [
                'total_requests' => $overallStats->total_requests ?? 0,
                'total_tokens' => $overallStats->total_tokens ?? 0,
                'total_cost' => $overallStats->total_cost ?? 0,
            ],
        ]);
    }

    /**
     * Display AI usage statistics
     */
    public function usage(Request $request)
    {
        $days = $request->input('days', 30);
        $feature = $request->input('feature');

        // Feature-wise statistics
        $featureStats = [];
        $features = ['chat', 'quiz', 'whiteboard', 'image_generation'];

        foreach ($features as $feat) {
            $featureStats[$feat] = AiUsageTracking::getFeatureStats($feat, $days);
        }

        // Model-wise breakdown
        $modelStats = AiUsageTracking::select('model_name')
            ->selectRaw('
                COUNT(*) as total_requests,
                SUM(input_tokens) as total_input_tokens,
                SUM(output_tokens) as total_output_tokens,
                SUM(total_tokens) as total_tokens,
                SUM(estimated_cost) as total_cost
            ')
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('model_name')
            ->get();

        // Daily usage chart data (last 30 days)
        $dailyUsage = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dayStats = AiUsageTracking::whereDate('created_at', $date)
                ->selectRaw('
                    SUM(total_tokens) as tokens,
                    SUM(estimated_cost) as cost
                ')
                ->first();

            $dailyUsage[] = [
                'date' => Carbon::parse($date)->format('M d'),
                'tokens' => $dayStats->tokens ?? 0,
                'cost' => $dayStats->cost ?? 0,
            ];
        }

        // Top users by cost
        $topUsers = AiUsageTracking::select('user_id')
            ->selectRaw('
                SUM(total_tokens) as total_tokens,
                SUM(estimated_cost) as total_cost,
                COUNT(*) as request_count
            ')
            ->where('created_at', '>=', now()->subDays($days))
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->orderByDesc('total_cost')
            ->limit(10)
            ->with('user:id,name,email,mobile')
            ->get();

        // Overall statistics
        $overallStats = AiUsageTracking::where('created_at', '>=', now()->subDays($days))
            ->selectRaw('
                COUNT(*) as total_requests,
                SUM(input_tokens) as total_input_tokens,
                SUM(output_tokens) as total_output_tokens,
                SUM(total_tokens) as total_tokens,
                SUM(estimated_cost) as total_cost
            ')
            ->first();

        return view('admin.ai-configuration.usage', compact(
            'featureStats',
            'modelStats',
            'dailyUsage',
            'topUsers',
            'overallStats',
            'days'
        ));
    }

    /**
     * Export usage data to CSV
     */
    public function exportUsage(Request $request)
    {
        $days = $request->input('days', 30);

        $usage = AiUsageTracking::with('user:id,name,email')
            ->where('created_at', '>=', now()->subDays($days))
            ->get();

        $filename = 'ai_usage_' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($usage) {
            $file = fopen('php://output', 'w');

            // CSV headers
            fputcsv($file, [
                'Date',
                'User',
                'Feature',
                'Model',
                'Provider',
                'Input Tokens',
                'Output Tokens',
                'Total Tokens',
                'Estimated Cost (USD)'
            ]);

            // CSV rows
            foreach ($usage as $record) {
                fputcsv($file, [
                    $record->created_at->format('Y-m-d H:i:s'),
                    $record->user?->name ?? 'N/A',
                    $record->feature,
                    $record->model_name,
                    $record->provider,
                    $record->input_tokens,
                    $record->output_tokens,
                    $record->total_tokens,
                    number_format($record->estimated_cost, 6)
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // REMOVED: Model configuration methods
    // Models are now configured per-feature:
    // - Chat: Force uses Model 83 in UnifiedAIService
    // - Quiz: /admin/quiz-generator/settings
    // - Whiteboard: /admin/whiteboard-video/settings
}
