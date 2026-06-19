<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use Illuminate\Http\Request;

class AiModelController extends Controller
{
    /**
     * Display a listing of AI models
     */
    public function index()
    {
        $aiModels = AiModel::orderBy('order')->get();
        return view('admin.ai-models.index', compact('aiModels'));
    }

    /**
     * Update AI model settings
     */
    public function update(Request $request, AiModel $aiModel)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'api_key' => 'nullable|string',
            'is_active' => 'boolean',
            'max_tokens' => 'integer|min:100|max:10000',
            'temperature' => 'numeric|min:0|max:2',
            'order' => 'integer|min:0',
        ]);

        $aiModel->update($validated);

        return redirect()->route('admin.ai-models.index')
            ->with('success', 'AI Model updated successfully');
    }

    /**
     * Toggle AI model active status
     */
    public function toggleActive(Request $request, AiModel $aiModel)
    {
        $isActive = $request->input('is_active', !$aiModel->is_active);
        $aiModel->update(['is_active' => $isActive]);

        return response()->json([
            'success' => true,
            'is_active' => $aiModel->is_active,
            'model_name' => $aiModel->name
        ]);
    }
}
