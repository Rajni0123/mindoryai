<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PythonAIService;
use Illuminate\Http\Request;

class AIController extends Controller
{
    protected PythonAIService $ai;

    public function __construct(PythonAIService $ai)
    {
        $this->ai = $ai;
    }

    /**
     * POST /api/ai/ask
     * Fast text → AI answer endpoint.
     */
    public function ask(Request $request)
    {
        $request->validate([
            'text'          => 'required|string|max:5000',
            'system_prompt' => 'nullable|string|max:2000',
            'temperature'   => 'nullable|numeric|min:0|max:2',
            'max_tokens'    => 'nullable|integer|min:50|max:8192',
        ]);

        $result = $this->ai->ask(
            text:         $request->input('text'),
            systemPrompt: $request->input('system_prompt'),
            temperature:  $request->input('temperature', 0.7),
            maxTokens:    $request->input('max_tokens', 2048),
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'error'   => $result['error'] ?? 'AI service unavailable',
            ], 502);
        }

        return response()->json([
            'success'    => true,
            'answer'     => $result['answer'],
            'cached'     => $result['cached'],
            'source'     => $result['source'] ?? 'unknown',
            'latency_ms' => $result['latency_ms'] ?? 0,
        ]);
    }

    /**
     * GET /api/ai/health
     * Check AI microservice status.
     */
    public function health()
    {
        return response()->json($this->ai->health());
    }
}
