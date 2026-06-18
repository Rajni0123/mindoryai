<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FeatureModelsController extends Controller
{
    /**
     * Display the feature-specific AI models configuration page
     */
    public function index()
    {
        // Get all active AI models
        $aiModels = AiModel::where('is_active', true)
            ->orderBy('name')
            ->get();

        // Get current model assignments from settings
        $currentModels = [
            'chat' => Setting::where('key', 'feature_model_chat')->value('value'),
            'quiz' => Setting::where('key', 'feature_model_quiz')->value('value'),
            'whiteboard' => Setting::where('key', 'feature_model_whiteboard')->value('value'),
            'image_generation' => Setting::where('key', 'feature_model_image_generation')->value('value'),
        ];

        return view('admin.feature-models', compact('aiModels', 'currentModels'));
    }

    /**
     * Update feature-specific AI model assignments
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'chat_model_id' => 'nullable|integer|exists:ai_models,id',
            'quiz_model_id' => 'nullable|integer|exists:ai_models,id',
            'whiteboard_model_id' => 'nullable|integer|exists:ai_models,id',
            'image_generation_model_id' => 'nullable|integer|exists:ai_models,id',
        ]);

        try {
            DB::beginTransaction();

            // Update or create settings for each feature
            $features = [
                'chat' => $validated['chat_model_id'] ?? null,
                'quiz' => $validated['quiz_model_id'] ?? null,
                'whiteboard' => $validated['whiteboard_model_id'] ?? null,
                'image_generation' => $validated['image_generation_model_id'] ?? null,
            ];

            foreach ($features as $feature => $modelId) {
                Setting::updateOrCreate(
                    ['key' => "feature_model_{$feature}"],
                    ['value' => $modelId]
                );
            }

            DB::commit();

            return redirect()
                ->route('admin.feature-models.index')
                ->with('success', 'Feature-specific AI models updated successfully! Each feature will now use its dedicated model.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to update feature models', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()
                ->back()
                ->withErrors(['error' => 'Failed to update models: ' . $e->getMessage()])
                ->withInput();
        }
    }
}
