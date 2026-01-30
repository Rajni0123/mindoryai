<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ImageGenerationController extends Controller
{
    // Study-related keywords for validation
    private $studyKeywords = [
        // Subjects
        'history', 'geography', 'economics', 'mathematics', 'maths', 'biology',
        'physics', 'chemistry', 'science', 'political science', 'sociology',
        'english', 'hindi', 'sanskrit', 'psychology', 'philosophy', 'education',

        // UPSC/Competitive exams
        'upsc', 'ias', 'ips', 'ifs', 'competitive exam', 'civil services',
        'prelims', 'mains', 'interview', 'general studies', 'gs',

        // Education levels
        'class 10', 'class 12', 'cbse', 'icse', 'board exam', 'ncert',
        'textbook', 'syllabus', 'chapter', 'lesson', 'diagram', 'chart',
        'graph', 'map', 'illustration', 'formula', 'equation',

        // India specific
        'india map', 'indian constitution', 'indian history', 'indian geography',
        'states of india', 'rivers of india', 'mountains of india',

        // Academic terms
        'explain', 'definition', 'concept', 'theory', 'principle', 'law',
        'theorem', 'diagram showing', 'illustration of', 'step by step',
        'solution', 'answer', 'question', 'problem', 'example',

        // Visual learning
        'timeline', 'flowchart', 'mind map', 'hierarchy', 'classification',
        'structure', 'anatomy', 'solar system', 'human body', 'cell structure',
        'chemical structure', 'molecular', 'atomic', 'periodic table',
    ];

    /**
     * Check if the prompt is study-related
     */
    private function isStudyRelated($prompt)
    {
        $promptLower = strtolower($prompt);

        // Check if any study keyword is present
        foreach ($this->studyKeywords as $keyword) {
            if (strpos($promptLower, $keyword) !== false) {
                return true;
            }
        }

        // Check for image generation related keywords
        $imageKeywords = ['generate', 'create', 'make', 'draw', 'show', 'illustrate', 'image', 'picture', 'diagram'];
        $hasImageKeyword = false;
        foreach ($imageKeywords as $keyword) {
            if (strpos($promptLower, $keyword) !== false) {
                $hasImageKeyword = true;
                break;
            }
        }

        // If it's asking for an image but not study-related, block it
        if ($hasImageKeyword) {
            return false; // Image generation request but not study-related
        }

        // Default to false for safety
        return false;
    }

    /**
     * Generate an educational image using Pollinations.ai
     */
    public function generate(Request $request)
    {
        // Increase execution time limit to prevent timeout during image generation
        set_time_limit(120);

        $validated = $request->validate([
            'prompt' => 'required|string|max:1000',
            'chat_id' => 'required|integer',
        ]);

        $prompt = $validated['prompt'];
        $chatId = $validated['chat_id'];

        Log::info('=== Image Generation Request ===');
        Log::info('Prompt:', ['prompt' => $prompt]);

        // Check if the prompt is study-related
        if (!$this->isStudyRelated($prompt)) {
            Log::warning('Non-study image generation blocked', ['prompt' => $prompt]);

            return response()->json([
                'success' => false,
                'error' => 'Image generation is only available for study-related content.',
                'allowed_topics' => [
                    'History', 'Geography', 'Economics', 'Mathematics', 'Science',
                    'Biology', 'Physics', 'Chemistry', 'UPSC Preparation',
                    'Maps', 'Diagrams', 'Charts', 'Illustrations', 'Formulas'
                ],
                'message' => 'Please ask for educational images like diagrams, maps, charts, or illustrations related to your studies.',
            ], 403);
        }

        try {
            Log::info('Starting image generation for prompt: ' . $prompt);

            // Enhance the prompt for educational content
            $enhancedPrompt = "Educational diagram illustration for students: " . $prompt .
                ", clear labeled textbook style, professional scientific illustration, white background, high quality";

            // URL encode the prompt for Pollinations.ai (free AI image generation)
            $encodedPrompt = urlencode($enhancedPrompt);

            // Generate image URL using Pollinations.ai (free, no API key required)
            // Pollinations.ai generates images on-demand when the URL is accessed
            // We return the URL immediately - no need to verify as it will generate on client access
            $imageUrl = "https://image.pollinations.ai/prompt/{$encodedPrompt}?width=1024&height=1024&nologo=true&seed=" . time();

            Log::info('Generated Pollinations URL', ['url' => $imageUrl]);

            // Save to chat history immediately (URL is always valid format)
            $this->saveImageToChat($chatId, $prompt, $imageUrl);

            return response()->json([
                'success' => true,
                'image_url' => $imageUrl,
                'prompt' => $prompt,
                'provider' => 'pollinations'
            ]);

        } catch (\Exception $e) {
            Log::error('=== Image Generation Exception ===');
            Log::error('Message: ' . $e->getMessage());
            Log::error('Trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'error' => 'An error occurred during image generation',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Save base64 image to storage and return URL
     */
    private function saveBase64Image($base64Data, $mimeType = 'image/png')
    {
        try {
            $extension = 'png';
            if ($mimeType === 'image/jpeg' || $mimeType === 'image/jpg') {
                $extension = 'jpg';
            } elseif ($mimeType === 'image/webp') {
                $extension = 'webp';
            }

            $fileName = 'generated_' . time() . '_' . uniqid() . '.' . $extension;
            $path = 'generated-images/' . $fileName;

            // Decode base64 and save to storage
            $imageData = base64_decode($base64Data);
            \Illuminate\Support\Facades\Storage::disk('public')->put($path, $imageData);

            // Return the public URL
            return asset('storage/' . $path);

        } catch (\Exception $e) {
            Log::error('Failed to save generated image', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Save generated image to chat history
     */
    private function saveImageToChat($chatId, $prompt, $imageUrl)
    {
        try {
            $chat = \App\Models\MobileChat::find($chatId);
            if (!$chat) {
                Log::error('Chat not found', ['chat_id' => $chatId]);
                return;
            }

            $message = new \App\Models\MobileChatMessage();
            $message->mobile_chat_id = $chatId;
            $message->sender = 'assistant';
            $message->content = "📊 **Generated Image**\n\nBased on your request: *\"{$prompt}\"*";
            $message->image_url = $imageUrl;
            $message->is_image_generation = true;
            $message->created_at = now();
            $message->updated_at = now();
            $message->save();

            Log::info('Image saved to chat', [
                'message_id' => $message->id,
                'chat_id' => $chatId
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to save image to chat', [
                'error' => $e->getMessage()
            ]);
        }
    }
}
