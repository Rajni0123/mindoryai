<?php

namespace App\Services;

use App\Models\AiSystemPrompt;
use App\Models\AiUsageTracking;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class GeminiService
{
    private string $apiKey;
    private string $model;
    private string $feature;
    private ?int $userId;
    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/';

    /**
     * Initialize Gemini Service
     *
     * @param string $feature Feature name (chat, quiz, whiteboard, image_generation)
     * @param string|null $modelName Model to use (defaults to gemini-1.5-flash)
     * @param int|null $userId User ID for tracking
     */
    public function __construct(
        string $feature,
        ?string $modelName = null,
        ?int $userId = null
    ) {
        // Get API key from FrontendConfig (admin panel saves here)
        $this->apiKey = \App\Models\FrontendConfig::getValue('ai.gemini_api_key', '');

        $this->model = $modelName ?? 'gemini-1.5-flash';
        $this->feature = $feature;
        $this->userId = $userId;

        if (empty($this->apiKey)) {
            throw new Exception('Gemini API key not configured. Please set it in Admin Panel → AI Settings → Google Gemini section');
        }
    }

    /**
     * Generate content with Gemini
     *
     * @param string $userPrompt User's prompt/question
     * @param array $options Additional options (temperature, maxTokens, etc.)
     * @return array Response with content and usage data
     */
    public function generateContent(string $userPrompt, array $options = []): array
    {
        try {
            $systemPrompt = AiSystemPrompt::getPromptFor($this->feature);
            $fullPrompt = $systemPrompt
                ? "{$systemPrompt}\n\n{$userPrompt}"
                : $userPrompt;

            $requestPayload = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $fullPrompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => $options['temperature'] ?? 0.7,
                    'topK' => $options['topK'] ?? 40,
                    'topP' => $options['topP'] ?? 0.95,
                    'maxOutputTokens' => $options['maxOutputTokens'] ?? 8192,
                ],
            ];

            // Note: thinking_level is not yet supported by Gemini API
            // Keeping this commented for future use when Google adds this feature
            // if (isset($options['thinking_level'])) {
            //     $requestPayload['generationConfig']['thinking_level'] = $options['thinking_level'];
            // }

            // Add JSON mode if requested
            if ($options['jsonMode'] ?? false) {
                $requestPayload['generationConfig']['responseMimeType'] = 'application/json';
            }

            // Add safety settings if provided
            if (isset($options['safetySettings'])) {
                $requestPayload['safetySettings'] = $options['safetySettings'];
            }

            $apiUrl = "{$this->baseUrl}{$this->model}:generateContent?key={$this->apiKey}";

            Log::info("Gemini API Request", [
                'feature' => $this->feature,
                'model' => $this->model,
                'prompt_length' => strlen($userPrompt),
            ]);

            $response = Http::timeout($options['timeout'] ?? 120)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->withOptions(['verify' => false]) // Disable SSL verification for local development
                ->post($apiUrl, $requestPayload);

            if (!$response->successful()) {
                throw new Exception("Gemini API Error: " . $response->body());
            }

            $data = $response->json();

            // Extract content and usage
            $content = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

            // Sanitize UTF-8 content to prevent encoding issues
            $content = $this->sanitizeUtf8($content);

            $inputTokens = $data['usageMetadata']['promptTokenCount'] ?? 0;
            $outputTokens = $data['usageMetadata']['candidatesTokenCount'] ?? 0;

            // Track usage
            if ($inputTokens > 0 || $outputTokens > 0) {
                AiUsageTracking::track(
                    feature: $this->feature,
                    modelName: $this->model,
                    inputTokens: $inputTokens,
                    outputTokens: $outputTokens,
                    userId: $this->userId,
                    provider: 'google',
                    metadata: [
                        'prompt_length' => strlen($userPrompt),
                        'response_length' => strlen($content),
                    ]
                );
            }

            Log::info("Gemini API Success", [
                'feature' => $this->feature,
                'model' => $this->model,
                'input_tokens' => $inputTokens,
                'output_tokens' => $outputTokens,
            ]);

            return [
                'content' => $content,
                'usage' => [
                    'input_tokens' => $inputTokens,
                    'output_tokens' => $outputTokens,
                    'total_tokens' => $inputTokens + $outputTokens,
                ],
                'model' => $this->model,
            ];

        } catch (Exception $e) {
            Log::error("Gemini API Error", [
                'feature' => $this->feature,
                'model' => $this->model,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Generate content with vision (image analysis)
     *
     * @param string $userPrompt Text prompt
     * @param string $imageData Base64 encoded image data
     * @param string $mimeType Image MIME type
     * @param array $options Additional options
     * @return array Response with content and usage data
     */
    public function generateContentWithVision(
        string $userPrompt,
        string $imageData,
        string $mimeType,
        array $options = []
    ): array {
        try {
            $systemPrompt = AiSystemPrompt::getPromptFor($this->feature);
            $fullPrompt = $systemPrompt
                ? "{$systemPrompt}\n\n{$userPrompt}"
                : $userPrompt;

            $requestPayload = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $fullPrompt],
                            [
                                'inline_data' => [
                                    'mime_type' => $mimeType,
                                    'data' => $imageData
                                ]
                            ]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => $options['temperature'] ?? 0.4,
                    'topK' => $options['topK'] ?? 32,
                    'topP' => $options['topP'] ?? 1,
                    'maxOutputTokens' => $options['maxOutputTokens'] ?? 4096,
                ],
            ];

            // Note: thinking_level is not yet supported by Gemini API
            // Keeping this commented for future use when Google adds this feature
            // if (isset($options['thinking_level'])) {
            //     $requestPayload['generationConfig']['thinking_level'] = $options['thinking_level'];
            // }

            $apiUrl = "{$this->baseUrl}{$this->model}:generateContent?key={$this->apiKey}";

            $response = Http::timeout($options['timeout'] ?? 90)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->withOptions(['verify' => false]) // Disable SSL verification for local development
                ->post($apiUrl, $requestPayload);

            if (!$response->successful()) {
                throw new Exception("Gemini Vision API Error: " . $response->body());
            }

            $data = $response->json();

            $content = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

            // Sanitize UTF-8 content to prevent encoding issues
            $content = $this->sanitizeUtf8($content);

            $inputTokens = $data['usageMetadata']['promptTokenCount'] ?? 0;
            $outputTokens = $data['usageMetadata']['candidatesTokenCount'] ?? 0;

            // Track usage
            AiUsageTracking::track(
                feature: $this->feature,
                modelName: $this->model,
                inputTokens: $inputTokens,
                outputTokens: $outputTokens,
                userId: $this->userId,
                provider: 'google',
                metadata: ['request_type' => 'vision', 'mime_type' => $mimeType]
            );

            Log::info("Gemini Vision API Success", [
                'feature' => $this->feature,
                'model' => $this->model,
                'input_tokens' => $inputTokens,
                'output_tokens' => $outputTokens,
            ]);

            return [
                'content' => $content,
                'usage' => [
                    'input_tokens' => $inputTokens,
                    'output_tokens' => $outputTokens,
                    'total_tokens' => $inputTokens + $outputTokens,
                ],
                'model' => $this->model,
            ];

        } catch (Exception $e) {
            Log::error("Gemini Vision API Error", [
                'feature' => $this->feature,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Set a different model for this instance
     */
    public function setModel(string $modelName): self
    {
        $this->model = $modelName;
        return $this;
    }

    /**
     * Get current model name
     */
    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * Sanitize UTF-8 string to fix encoding issues from API responses
     * Critical for handling responses that may contain invalid UTF-8 sequences
     */
    private function sanitizeUtf8(string $string): string
    {
        // Remove BOM if present
        $string = preg_replace('/^\xEF\xBB\xBF/', '', $string);

        // Remove null bytes
        $string = str_replace("\0", '', $string);

        // Use iconv to strip invalid UTF-8 sequences (most reliable method)
        $cleaned = @iconv('UTF-8', 'UTF-8//IGNORE', $string);
        if ($cleaned !== false) {
            $string = $cleaned;
        }

        // If still not valid UTF-8, try to detect and convert encoding
        if (!mb_check_encoding($string, 'UTF-8')) {
            $encoding = mb_detect_encoding($string, ['UTF-8', 'ISO-8859-1', 'Windows-1252', 'ASCII'], true);
            if ($encoding && $encoding !== 'UTF-8') {
                $converted = @mb_convert_encoding($string, 'UTF-8', $encoding);
                if ($converted !== false) {
                    $string = $converted;
                }
            }
        }

        // Remove control characters except newline, tab, carriage return
        $string = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $string);

        // Remove Unicode replacement characters that indicate encoding errors
        $string = str_replace("\xEF\xBF\xBD", '', $string);

        return $string;
    }
}
