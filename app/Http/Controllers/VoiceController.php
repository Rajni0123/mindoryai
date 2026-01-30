<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VoiceController extends Controller
{
    /**
     * Transcribe audio to text using OpenAI Whisper API
     */
    public function transcribe(Request $request)
    {
        // Log incoming request for debugging
        Log::info('=== Voice transcription request received ===');
        Log::info('Has audio file:', ['has_audio' => $request->hasFile('audio')]);
        Log::info('All files:', ['all_files' => array_keys($request->allFiles())]);
        Log::info('Content-Type:', ['content_type' => $request->header('Content-Type')]);

        // Validate the request
        try {
            $validated = $request->validate([
                'audio' => 'required|file|mimes:audio/mpeg,audio/mp4,audio/wav,audio/x-wav,audio/webm|max:25000',
            ], [
                'audio.required' => 'Audio file is required',
                'audio.file' => 'The uploaded file must be an audio file',
                'audio.mimes' => 'The audio file must be in M4A, MP3, WAV, or WebM format',
                'audio.max' => 'The audio file must not be larger than 25MB',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed', ['errors' => $e->errors()]);
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'details' => $e->errors(),
            ], 422);
        }

        try {
            $audioFile = $request->file('audio');

            Log::info('Audio file details', [
                'original_name' => $audioFile->getClientOriginalName(),
                'mime_type' => $audioFile->getMimeType(),
                'client_mime_type' => $audioFile->getClientMimeType(),
                'extension' => $audioFile->getClientOriginalExtension(),
                'size' => $audioFile->getSize(),
                'is_valid' => $audioFile->isValid(),
                'error' => $audioFile->getErrorMessage(),
            ]);

            // Check if file is valid
            if (!$audioFile->isValid()) {
                Log::error('File upload validation failed', [
                    'error_code' => $audioFile->getError(),
                    'error_message' => $audioFile->getErrorMessage(),
                ]);
                return response()->json([
                    'success' => false,
                    'error' => 'File upload failed',
                    'details' => $audioFile->getErrorMessage(),
                ], 422);
            }

            // Get the real path of the uploaded file
            $realPath = $audioFile->getRealPath();
            Log::info('File real path:', ['path' => $realPath]);

            if (!$realPath || !file_exists($realPath)) {
                Log::error('File does not exist at real path', [
                    'realPath' => $realPath,
                    'exists' => file_exists($realPath),
                ]);
                return response()->json([
                    'success' => false,
                    'error' => 'File could not be read from server',
                    'details' => 'The uploaded file could not be accessed',
                ], 500);
            }

            // Read file content
            $fileContent = file_get_contents($realPath);
            if ($fileContent === false) {
                Log::error('Failed to read file content');
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to read file',
                ], 500);
            }

            Log::info('File content loaded', ['size' => strlen($fileContent)]);

            // Get OpenAI API key
            $apiKey = env('OPENAI_API_KEY');
            if (!$apiKey) {
                Log::error('OpenAI API key not configured');
                return response()->json([
                    'success' => false,
                    'error' => 'OpenAI API key not configured',
                ], 500);
            }

            // Send to OpenAI Whisper API
            $httpClient = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
            ]);

            // Only disable SSL verification in local development
            if (config('app.env') === 'local') {
                $httpClient = $httpClient->withOptions([
                    'verify' => false, // Disable SSL verification for development
                ]);
            }

            Log::info('Sending to OpenAI Whisper API');

            $response = $httpClient
                ->attach('file', $fileContent, $audioFile->getClientOriginalName())
                ->post('https://api.openai.com/v1/audio/transcriptions', [
                    'model' => 'whisper-1',
                ]);

            Log::info('OpenAI Whisper Response', [
                'status' => $response->status(),
                'successful' => $response->successful(),
                'body' => $response->body(),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $text = $data['text'] ?? '';

                Log::info('Transcription successful', ['text_length' => strlen($text)]);

                return response()->json([
                    'success' => true,
                    'text' => $text,
                ]);
            }

            Log::error('Whisper API Error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to transcribe audio',
                'details' => 'OpenAI API error: ' . $response->body(),
            ], 500);

        } catch (\Exception $e) {
            Log::error('=== Transcription Exception ===');
            Log::error('Message:', $e->getMessage());
            Log::error('File:', $e->getFile());
            Log::error('Line:', $e->getLine());
            Log::error('Trace:', $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'error' => 'An error occurred during transcription',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Summarize text using OpenAI GPT
     */
    public function summarize(Request $request)
    {
        $validated = $request->validate([
            'text' => 'required|string|max:10000',
        ]);

        try {
            $text = $validated['text'];
            $apiKey = env('OPENAI_API_KEY');

            if (!$apiKey) {
                return response()->json([
                    'success' => false,
                    'error' => 'OpenAI API key not configured',
                ], 500);
            }

            // Send to OpenAI API for summarization
            $httpClient = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ]);

            // Only disable SSL verification in local development
            if (config('app.env') === 'local') {
                $httpClient = $httpClient->withOptions([
                    'verify' => false,
                ]);
            }

            $response = $httpClient->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a helpful assistant that summarizes text concisely. Provide a brief, clear summary of the given text in 2-3 sentences.'
                    ],
                    [
                        'role' => 'user',
                        'content' => "Please summarize the following text:\n\n{$text}"
                    ]
                ],
                'max_tokens' => 150,
                'temperature' => 0.5,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $summary = $data['choices'][0]['message']['content'] ?? '';

                Log::info('Text summarized successfully');

                return response()->json([
                    'success' => true,
                    'summary' => trim($summary),
                    'original_length' => strlen($text),
                    'summary_length' => strlen($summary),
                ]);
            }

            Log::error('OpenAI API Error: ' . $response->body());

            return response()->json([
                'success' => false,
                'error' => 'Failed to summarize text',
            ], 500);

        } catch (\Exception $e) {
            Log::error('Summarization Exception: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'An error occurred during summarization',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
