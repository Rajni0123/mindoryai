<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\AiModel;
use Illuminate\Http\Request;

class QuizGeneratorSettingsController extends Controller
{
    /**
     * Display quiz generator settings
     */
    public function index()
    {
        $settings = [
            'model_id' => Setting::get('quiz_generator_model_id', '83'),
        ];

        // Get only active AI models
        $aiModels = AiModel::where('is_active', true)
            ->orderBy('provider')
            ->orderBy('order')
            ->get();

        return view('admin.quiz-generator.settings', compact('settings', 'aiModels'));
    }

    /**
     * Update quiz generator settings
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'model_id' => 'required|integer|exists:ai_models,id',
        ]);

        Setting::set('quiz_generator_model_id', $validated['model_id'], 'features');
        Setting::clearCache();

        return redirect()->route('admin.quiz-generator.settings')
            ->with('success', 'Quiz generator settings updated successfully!');
    }
}
