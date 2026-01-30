<?php

namespace App\Services\WhiteboardVideo;

use Google\Cloud\TextToSpeech\V1\AudioConfig;
use Google\Cloud\TextToSpeech\V1\AudioEncoding;
use Google\Cloud\TextToSpeech\V1\SsmlVoiceGender;
use Google\Cloud\TextToSpeech\V1\SynthesisInput;
use Google\Cloud\TextToSpeech\V1\TextToSpeechClient;
use Google\Cloud\TextToSpeech\V1\VoiceSelectionParams;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class GoogleTTSService
{
    private TextToSpeechClient $client;
    private string $languageCode;
    private string $voiceName;
    private float $speakingRate;
    private float $pitch = 0.0;

    public function __construct()
    {
        try {
            // Get settings from database
            $this->voiceName = \App\Models\Setting::get('whiteboard_tts_voice', 'hi-IN-Neural2-A');
            $this->speakingRate = (float) \App\Models\Setting::get('whiteboard_tts_speaking_rate', '1.0');

            // Extract language code from voice name (e.g., 'hi-IN-Neural2-A' -> 'hi-IN')
            $parts = explode('-', $this->voiceName);
            $this->languageCode = count($parts) >= 2 ? $parts[0] . '-' . $parts[1] : 'hi-IN';

            $credentialsPath = config('services.google.credentials_path');

            putenv("GOOGLE_APPLICATION_CREDENTIALS={$credentialsPath}");

            $this->client = new TextToSpeechClient();

        } catch (Exception $e) {
            Log::error('Failed to initialize Google TTS client', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Generate audio from narration text
     *
     * @param string $narration
     * @param int $sceneNumber
     * @param string $outputPath
     * @return array ['path' => string, 'duration' => float]
     * @throws Exception
     */
    public function generateAudio(
        string $narration,
        int $sceneNumber,
        string $outputPath
    ): array {
        try {
            Log::info("Generating audio for scene {$sceneNumber}", [
                'text_length' => strlen($narration),
            ]);

            // Prepare synthesis input
            $input = (new SynthesisInput())->setText($narration);

            // Configure voice parameters
            $voice = (new VoiceSelectionParams())
                ->setLanguageCode($this->languageCode)
                ->setName($this->voiceName)
                ->setSsmlGender(SsmlVoiceGender::NEUTRAL);

            // Configure audio settings
            $audioConfig = (new AudioConfig())
                ->setAudioEncoding(AudioEncoding::MP3)
                ->setSpeakingRate($this->speakingRate)
                ->setPitch($this->pitch)
                ->setEffectsProfileId(['headphone-class-device']);

            // Synthesize speech
            $response = $this->client->synthesizeSpeech($input, $voice, $audioConfig);

            // Get audio content
            $audioContent = $response->getAudioContent();

            // Save audio file
            $filename = "audio_{$sceneNumber}.mp3";
            $fullPath = "{$outputPath}/{$filename}";

            Storage::put($fullPath, $audioContent);

            // Get audio duration
            $duration = $this->getAudioDuration(storage_path("app/{$fullPath}"));

            Log::info("Audio generated successfully for scene {$sceneNumber}", [
                'path' => $fullPath,
                'duration' => $duration,
                'size' => strlen($audioContent),
            ]);

            return [
                'path' => $fullPath,
                'duration' => $duration,
            ];

        } catch (Exception $e) {
            Log::error("Failed to generate audio for scene {$sceneNumber}", [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get audio duration using getID3 or ffprobe
     *
     * @param string $filePath
     * @return float Duration in seconds
     */
    private function getAudioDuration(string $filePath): float
    {
        try {
            // Try using ffprobe first
            $command = sprintf(
                'ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 "%s"',
                $filePath
            );

            $output = shell_exec($command);

            if ($output && is_numeric(trim($output))) {
                return (float) trim($output);
            }

            // Fallback: estimate based on word count (average speaking rate: 150 wpm)
            Log::warning('Could not get exact audio duration, using estimation');
            return 10.0; // Default fallback

        } catch (Exception $e) {
            Log::warning('Failed to get audio duration', [
                'error' => $e->getMessage(),
            ]);

            return 10.0; // Default fallback
        }
    }

    /**
     * Batch generate audio for multiple scenes
     *
     * @param array $scenes
     * @param string $outputPath
     * @return array Audio paths with durations
     */
    public function batchGenerateAudio(array $scenes, string $outputPath): array
    {
        $audioPaths = [];

        foreach ($scenes as $scene) {
            try {
                $sceneNumber = $scene['scene_number'];
                $narration = $scene['narration'];

                $audioData = $this->generateAudio(
                    $narration,
                    $sceneNumber,
                    $outputPath
                );

                $audioPaths[$sceneNumber] = $audioData;

                // Add small delay to avoid rate limiting
                usleep(300000); // 0.3 seconds

            } catch (Exception $e) {
                Log::error("Failed to generate audio for scene {$scene['scene_number']}", [
                    'error' => $e->getMessage(),
                ]);

                // Skip this scene or use silence
                $audioPaths[$scene['scene_number']] = [
                    'path' => null,
                    'duration' => $scene['duration'] ?? 10,
                ];
            }
        }

        return $audioPaths;
    }

    /**
     * Set custom voice parameters
     *
     * @param string $voiceName
     * @param float $speakingRate
     * @param float $pitch
     * @return self
     */
    public function setVoiceParameters(
        string $voiceName,
        float $speakingRate = 1.0,
        float $pitch = 0.0
    ): self {
        $this->voiceName = $voiceName;
        $this->speakingRate = max(0.25, min(4.0, $speakingRate));
        $this->pitch = max(-20.0, min(20.0, $pitch));

        return $this;
    }

    /**
     * Set language code
     *
     * @param string $languageCode
     * @return self
     */
    public function setLanguage(string $languageCode): self
    {
        $this->languageCode = $languageCode;

        return $this;
    }

    /**
     * Clean up resources
     */
    public function __destruct()
    {
        if (isset($this->client)) {
            $this->client->close();
        }
    }
}
