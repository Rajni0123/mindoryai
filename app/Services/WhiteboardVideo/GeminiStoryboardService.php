<?php

namespace App\Services\WhiteboardVideo;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class GeminiStoryboardService
{
    private string $apiKey;
    private string $model;
    private string $apiUrl;

    public function __construct()
    {
        // Get API key from FrontendConfig (admin panel saves here)
        $this->apiKey = \App\Models\FrontendConfig::getValue('ai.gemini_api_key', '');

        // Get WHITEBOARD-SPECIFIC model from whiteboard settings
        $whiteboardModelId = \App\Models\Setting::get('whiteboard_video_model_id', 83);
        $whiteboardModel = \App\Models\AiModel::find($whiteboardModelId);

        // Use whiteboard's configured model or fallback to gemini-2.0-flash
        $this->model = $whiteboardModel ? $whiteboardModel->model_identifier : 'gemini-2.0-flash';
        $this->apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent";
    }

    /**
     * Generate storyboard from document content using shared GeminiService
     *
     * @param string $documentContent
     * @return array
     * @throws Exception
     */
    public function generateStoryboard(string $documentContent): array
    {
        try {
            Log::info('Generating storyboard with Gemini 1.5 Flash', [
                'model' => $this->model,
                'content_length' => strlen($documentContent),
            ]);

            // Use shared GeminiService for whiteboard feature
            $geminiService = new \App\Services\GeminiService(
                feature: 'whiteboard',
                modelName: $this->model,
                userId: auth()->id()
            );

            $userPrompt = $this->buildUserPrompt($documentContent);

            // Generate storyboard with JSON mode
            $response = $geminiService->generateContent(
                userPrompt: $userPrompt,
                options: [
                    'temperature' => 0.7,
                    'topK' => 40,
                    'topP' => 0.95,
                    'maxOutputTokens' => 8192,
                    'jsonMode' => true, // Force JSON output
                    'safetySettings' => [
                        [
                            'category' => 'HARM_CATEGORY_HATE_SPEECH',
                            'threshold' => 'BLOCK_NONE'
                        ],
                        [
                            'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
                            'threshold' => 'BLOCK_NONE'
                        ],
                        [
                            'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                            'threshold' => 'BLOCK_NONE'
                        ],
                        [
                            'category' => 'HARM_CATEGORY_HARASSMENT',
                            'threshold' => 'BLOCK_NONE'
                        ],
                    ],
                ]
            );

            $jsonResponse = $response['content'];

            // Strip markdown code blocks if Gemini wrapped the JSON
            $jsonResponse = trim($jsonResponse);
            if (str_starts_with($jsonResponse, '```')) {
                $jsonResponse = preg_replace('/^```(?:json)?\s*/i', '', $jsonResponse);
                $jsonResponse = preg_replace('/\s*```$/', '', $jsonResponse);
                $jsonResponse = trim($jsonResponse);
            }

            // Parse JSON response
            $storyboard = json_decode($jsonResponse, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Storyboard JSON parse failed', [
                    'raw_response' => substr($jsonResponse, 0, 500),
                    'json_error' => json_last_error_msg(),
                ]);
                throw new Exception('Failed to parse JSON storyboard: ' . json_last_error_msg());
            }

            // Normalize various response structures into {scenes: [...]}
            $storyboard = $this->normalizeStoryboard($storyboard);

            // Validate storyboard structure
            $this->validateStoryboard($storyboard);

            Log::info('Storyboard generated successfully', [
                'total_scenes' => count($storyboard['scenes'] ?? []),
                'tokens_used' => $response['usage']['total_tokens'] ?? 0,
            ]);

            return $storyboard;

        } catch (Exception $e) {
            Log::error('Failed to generate storyboard', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Build system prompt for Gemini
     *
     * @return string
     */
    private function buildSystemPrompt(): string
    {
        return <<<PROMPT
You are an expert educational content creator specializing in creating whiteboard-style animated videos.

Your task is to convert any document or text into a structured JSON storyboard that will be used to generate a whiteboard animation video.

IMPORTANT RULES:
1. Output MUST be valid JSON only - no explanations, no markdown formatting
2. Break content into 5-10 clear scenes (max 15 for very long content)
3. Each scene should be 8-15 seconds long
4. Narration should be clear, conversational, and educational
5. Visual descriptions should be detailed instructions for generating minimalist whiteboard-style illustrations

JSON STRUCTURE REQUIRED:
{
  "title": "Video Title",
  "total_duration": estimated_total_seconds,
  "scenes": [
    {
      "scene_number": 1,
      "narration": "The script for voiceover - natural, conversational tone",
      "visual_description": "Detailed prompt for whiteboard image: Minimalist black ink sketch of [concept] on clean white background, simple line art, whiteboard animation style, no shading, clear and educational",
      "duration": seconds_based_on_word_count,
      "key_concepts": ["concept1", "concept2"]
    }
  ]
}

VISUAL DESCRIPTION GUIDELINES:
- ALWAYS start with: "Minimalist black ink sketch on white background showing..."
- Be specific about the elements to draw
- Use educational, clear diagram style
- Mention "simple line art", "whiteboard style", "no shading"
- Include arrows, labels, or annotations if needed for clarity
- Keep it simple - whiteboard animations work best with clear, uncluttered visuals

DURATION CALCULATION:
- Average speaking rate: 150 words per minute
- Calculate: (word_count / 150) * 60 = duration in seconds
- Minimum 8 seconds, maximum 15 seconds per scene
PROMPT;
    }

    /**
     * Build user prompt with document content
     *
     * @param string $documentContent
     * @return string
     */
    private function buildUserPrompt(string $documentContent): string
    {
        $systemInstructions = $this->buildSystemPrompt();

        return <<<PROMPT
{$systemInstructions}

---

Convert the following topic/document into a whiteboard animation storyboard.

Remember:
- Output VALID JSON ONLY - no markdown, no code blocks, no explanations
- The root object MUST have a "scenes" array
- 5-10 clear scenes
- Natural, educational narration
- Detailed whiteboard-style visual descriptions
- Appropriate durations based on word count

TOPIC/DOCUMENT:
{$documentContent}

Generate the JSON storyboard now (valid JSON only, must include "scenes" array):
PROMPT;
    }

    /**
     * Normalize various Gemini response structures into {title, scenes}
     */
    private function normalizeStoryboard(array $data): array
    {
        // Already has scenes at top level
        if (isset($data['scenes']) && is_array($data['scenes'])) {
            return $data;
        }

        // Nested: {"storyboard": {"scenes": [...]}}
        if (isset($data['storyboard']) && is_array($data['storyboard'])) {
            $inner = $data['storyboard'];
            if (isset($inner['scenes']) && is_array($inner['scenes'])) {
                if (!isset($inner['title']) && isset($data['title'])) {
                    $inner['title'] = $data['title'];
                }
                return $inner;
            }
            // {"storyboard": [{...}, {...}]} — scenes directly as list
            if (array_is_list($inner)) {
                return ['title' => $data['title'] ?? 'Whiteboard Video', 'scenes' => $inner];
            }
        }

        // Look for any key containing a list of dicts with narration
        foreach ($data as $key => $value) {
            if (is_array($value) && !empty($value) && is_array($value[0] ?? null)) {
                if (isset($value[0]['narration']) || isset($value[0]['visual_description'])) {
                    return ['title' => $data['title'] ?? 'Whiteboard Video', 'scenes' => $value];
                }
            }
        }

        // If data itself looks like a single scene
        if (isset($data['narration']) && isset($data['visual_description'])) {
            return ['title' => 'Whiteboard Video', 'scenes' => [$data]];
        }

        Log::warning('Cannot normalize storyboard', ['keys' => array_keys($data)]);
        return $data;
    }

    /**
     * Validate storyboard structure
     *
     * @param array $storyboard
     * @throws Exception
     */
    private function validateStoryboard(array $storyboard): void
    {
        if (!isset($storyboard['scenes']) || !is_array($storyboard['scenes'])) {
            throw new Exception('Storyboard must contain a scenes array');
        }

        if (empty($storyboard['scenes'])) {
            throw new Exception('Storyboard must contain at least one scene');
        }

        foreach ($storyboard['scenes'] as $index => $scene) {
            if (!isset($scene['narration'])) {
                throw new Exception("Scene {$index} missing narration");
            }

            if (!isset($scene['visual_description'])) {
                throw new Exception("Scene {$index} missing visual_description");
            }

            if (!isset($scene['duration'])) {
                throw new Exception("Scene {$index} missing duration");
            }

            if (!is_numeric($scene['duration']) || $scene['duration'] <= 0) {
                throw new Exception("Scene {$index} has invalid duration");
            }
        }
    }

    /**
     * Calculate estimated total duration from storyboard
     *
     * @param array $storyboard
     * @return int
     */
    public function calculateTotalDuration(array $storyboard): int
    {
        $totalDuration = 0;

        foreach ($storyboard['scenes'] ?? [] as $scene) {
            $totalDuration += (int) $scene['duration'];
        }

        return $totalDuration;
    }
}
