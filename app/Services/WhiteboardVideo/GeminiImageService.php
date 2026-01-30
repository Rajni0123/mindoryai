<?php

namespace App\Services\WhiteboardVideo;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class GeminiImageService
{
    private string $apiKey;
    private string $model = 'gemini-nano'; // Gemini 3 Nano for image generation prompts
    private string $apiUrl;

    public function __construct()
    {
        // Get API key from FrontendConfig (admin panel saves here)
        $this->apiKey = \App\Models\FrontendConfig::getValue('ai.gemini_api_key', '');

        // Get WHITEBOARD-SPECIFIC model from whiteboard settings
        $whiteboardModelId = \App\Models\Setting::get('whiteboard_video_model_id', 83);
        $whiteboardModel = \App\Models\AiModel::find($whiteboardModelId);

        // Use whiteboard's configured model or fallback
        $this->model = $whiteboardModel ? $whiteboardModel->model_identifier : 'gemini-2.0-flash';

        // Using Google AI Studio API (simpler than Vertex AI)
        $this->apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent";
    }

    /**
     * Generate whiteboard-style image
     *
     * @param string $visualDescription
     * @param int $sceneNumber
     * @param string $outputPath
     * @return string Path to generated image
     * @throws Exception
     */
    public function generateWhiteboardImage(
        string $visualDescription,
        int $sceneNumber,
        string $outputPath
    ): string {
        try {
            // Enhance prompt for whiteboard style
            $enhancedPrompt = $this->enhanceWhiteboardPrompt($visualDescription);

            Log::info("Generating whiteboard image for scene {$sceneNumber}", [
                'prompt' => $enhancedPrompt,
            ]);

            // Generate image using Gemini/Imagen API
            $imageData = $this->callImageAPI($enhancedPrompt);

            // Save image to storage
            $filename = "scene_{$sceneNumber}.png";
            $fullPath = "{$outputPath}/{$filename}";

            Storage::put($fullPath, $imageData);

            Log::info("Image generated successfully for scene {$sceneNumber}", [
                'path' => $fullPath,
                'size' => strlen($imageData),
            ]);

            return $fullPath;

        } catch (Exception $e) {
            Log::error("Failed to generate image for scene {$sceneNumber}", [
                'error' => $e->getMessage(),
            ]);

            // Fallback: generate placeholder image
            return $this->generatePlaceholderImage($sceneNumber, $outputPath, $visualDescription);
        }
    }

    /**
     * Enhance prompt specifically for whiteboard style
     *
     * @param string $visualDescription
     * @return string
     */
    private function enhanceWhiteboardPrompt(string $visualDescription): string
    {
        $whiteboardModifiers = [
            "whiteboard animation style",
            "simple black line drawing on pure white background",
            "minimalist educational illustration",
            "hand-drawn sketch style",
            "clean and clear diagram",
            "no colors except black lines",
            "high contrast black and white",
            "professional educational visual",
        ];

        return $visualDescription . ", " . implode(", ", $whiteboardModifiers);
    }

    /**
     * Generate image using programmatic whiteboard style
     * Since Gemini doesn't generate images, we create whiteboard-style graphics programmatically
     *
     * @param string $prompt
     * @return string Binary image data
     * @throws Exception
     */
    private function callImageAPI(string $prompt): string
    {
        try {
            // For now, generate programmatic whiteboard images
            // In future, this can be replaced with actual image generation API like DALL-E or Stable Diffusion
            return $this->generateProgrammaticWhiteboardImage($prompt);

        } catch (Exception $e) {
            Log::error('Image generation failed', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Generate a programmatic whiteboard-style image
     * Layout: Title at top, text content in upper area, decorative visuals at bottom
     * Uses TTF fonts for crisp, clear text rendering
     *
     * @param string $description
     * @return string Binary image data
     */
    private function generateProgrammaticWhiteboardImage(string $description): string
    {
        $width = 1920;
        $height = 1080;

        $image = imagecreatetruecolor($width, $height);
        imageantialias($image, true);

        // White background
        $white = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $white);

        // Colors
        $black = imagecolorallocate($image, 30, 30, 30);
        $darkGray = imagecolorallocate($image, 60, 60, 60);

        // Colorful border (thick, multi-color)
        $borderColors = [
            imagecolorallocate($image, 255, 107, 107), // coral red
            imagecolorallocate($image, 78, 205, 196),  // teal
            imagecolorallocate($image, 69, 183, 209),  // sky blue
            imagecolorallocate($image, 150, 206, 180), // mint green
        ];

        // Draw colorful border segments (top=red, right=teal, bottom=blue, left=green)
        $bw = 6; // border width
        for ($i = 0; $i < $bw; $i++) {
            // Top
            imageline($image, 10 + $i, 10 + $i, $width - 10 - $i, 10 + $i, $borderColors[0]);
            // Right
            imageline($image, $width - 10 - $i, 10 + $i, $width - 10 - $i, $height - 10 - $i, $borderColors[1]);
            // Bottom
            imageline($image, 10 + $i, $height - 10 - $i, $width - 10 - $i, $height - 10 - $i, $borderColors[2]);
            // Left
            imageline($image, 10 + $i, 10 + $i, 10 + $i, $height - 10 - $i, $borderColors[3]);
        }

        // Try to find a TTF font for crisp text
        $fontPath = $this->findTTFFont();

        // Parse description into text
        $text = $this->simplifyTextForDisplay($description);
        $lines = $this->wrapText($text, 70);

        if ($fontPath) {
            // ── TTF rendering (crisp and clear) ──
            $titleSize = 28;
            $bodySize = 20;

            // Title (first line) - centered at top
            $y = 80;
            if (!empty($lines[0])) {
                $titleLine = $lines[0];
                $bbox = imagettfbbox($titleSize, 0, $fontPath, $titleLine);
                $textWidth = $bbox[2] - $bbox[0];
                $x = (int)(($width - $textWidth) / 2);
                imagettftext($image, $titleSize, 0, $x, $y + $titleSize, $black, $fontPath, $titleLine);

                // Underline
                $underY = $y + $titleSize + 10;
                imagesetthickness($image, 3);
                imageline($image, $x, $underY, $x + $textWidth, $underY, $borderColors[0]);
                imagesetthickness($image, 1);

                $y = $underY + 30;
            }

            // Body lines - left aligned with padding
            $leftPad = 80;
            for ($index = 1; $index < count($lines); $index++) {
                if ($index > 8) break;
                $line = $lines[$index];
                imagettftext($image, $bodySize, 0, $leftPad, $y + $bodySize, $darkGray, $fontPath, $line);
                $y += (int)($bodySize * 1.8);
            }
        } else {
            // ── Fallback: GD built-in fonts ──
            $y = 100;
            foreach ($lines as $index => $line) {
                if ($index > 8) break;
                $fontSize = $index === 0 ? 5 : 4;
                $textWidth = imagefontwidth($fontSize) * strlen($line);
                $x = (int)(($width - $textWidth) / 2);
                imagestring($image, $fontSize, $x, $y, $line, $black);
                $y += ($index === 0 ? 60 : 40);
            }
        }

        // Add decorative elements at the BOTTOM of the image
        $this->addWhiteboardDecorations($image, $width, $height);

        // Convert to PNG
        ob_start();
        imagepng($image, null, 6);
        $imageData = ob_get_clean();
        imagedestroy($image);

        return $imageData;
    }

    /**
     * Find a usable TTF font on the system.
     */
    private function findTTFFont(): ?string
    {
        $candidates = [
            // Windows
            'C:/Windows/Fonts/arial.ttf',
            'C:/Windows/Fonts/segoeui.ttf',
            'C:/Windows/Fonts/calibri.ttf',
            'C:/Windows/Fonts/verdana.ttf',
            // Linux
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf',
            '/usr/share/fonts/truetype/freefont/FreeSans.ttf',
            '/usr/share/fonts/noto/NotoSans-Regular.ttf',
            // macOS
            '/Library/Fonts/Arial.ttf',
            '/System/Library/Fonts/Helvetica.ttc',
        ];

        foreach ($candidates as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Add colorful decorative elements at the bottom of the image
     */
    private function addWhiteboardDecorations($image, $width, $height): void
    {
        $coral = imagecolorallocate($image, 255, 107, 107);
        $teal = imagecolorallocate($image, 78, 205, 196);
        $blue = imagecolorallocate($image, 69, 183, 209);
        $mint = imagecolorallocate($image, 150, 206, 180);
        $purple = imagecolorallocate($image, 186, 147, 214);

        $bottomY = $height - 250;

        // Colorful circle
        imagesetthickness($image, 3);
        imageellipse($image, 300, $bottomY, 120, 120, $coral);
        imageellipse($image, 300, $bottomY, 110, 110, $coral);

        // Teal rectangle
        imagerectangle($image, 550, $bottomY - 60, 750, $bottomY + 60, $teal);
        imagerectangle($image, 554, $bottomY - 56, 746, $bottomY + 56, $teal);

        // Blue triangle
        $triPoints = [
            1000, $bottomY + 60,
            1100, $bottomY - 60,
            1200, $bottomY + 60,
        ];
        imagepolygon($image, $triPoints, 3, $blue);

        // Purple arrow
        imagesetthickness($image, 3);
        imageline($image, 1400, $bottomY, 1700, $bottomY, $purple);
        imageline($image, 1700, $bottomY, 1660, $bottomY - 25, $purple);
        imageline($image, 1700, $bottomY, 1660, $bottomY + 25, $purple);
        imagesetthickness($image, 1);
    }

    /**
     * Simplify text for display
     */
    private function simplifyTextForDisplay(string $text): string
    {
        // Remove extra whitespace and cleanup
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        // Limit length
        if (strlen($text) > 500) {
            $text = substr($text, 0, 500) . '...';
        }

        return $text;
    }

    /**
     * Wrap text into lines
     */
    private function wrapText(string $text, int $width): array
    {
        return explode("\n", wordwrap($text, $width, "\n", true));
    }

    /**
     * Generate placeholder image when API fails
     *
     * @param int $sceneNumber
     * @param string $outputPath
     * @param string $description
     * @return string
     */
    private function generatePlaceholderImage(
        int $sceneNumber,
        string $outputPath,
        string $description
    ): string {
        $width = 1920;
        $height = 1080;

        $image = imagecreatetruecolor($width, $height);
        imageantialias($image, true);

        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 30, 30, 30);
        $darkGray = imagecolorallocate($image, 80, 80, 80);
        $coral = imagecolorallocate($image, 255, 107, 107);

        imagefill($image, 0, 0, $white);

        // Colorful border
        for ($i = 0; $i < 5; $i++) {
            imagerectangle($image, 10 + $i, 10 + $i, $width - 10 - $i, $height - 10 - $i, $coral);
        }

        $fontPath = $this->findTTFFont();
        $titleText = "Scene {$sceneNumber}";
        $wrappedDescription = wordwrap(substr($description, 0, 300), 60, "\n");
        $descLines = explode("\n", $wrappedDescription);

        if ($fontPath) {
            // Title
            $bbox = imagettfbbox(30, 0, $fontPath, $titleText);
            $tw = $bbox[2] - $bbox[0];
            imagettftext($image, 30, 0, (int)(($width - $tw) / 2), 120, $black, $fontPath, $titleText);

            // Description
            $y = 200;
            foreach ($descLines as $line) {
                imagettftext($image, 18, 0, 80, $y, $darkGray, $fontPath, $line);
                $y += 36;
            }
        } else {
            imagestring($image, 5, (int)($width / 2 - 80), 80, $titleText, $black);
            $y = 160;
            foreach ($descLines as $line) {
                imagestring($image, 4, 80, $y, $line, $darkGray);
                $y += 28;
            }
        }

        // Save as PNG
        $filename = "scene_{$sceneNumber}.png";
        $fullPath = "{$outputPath}/{$filename}";
        $tempPath = storage_path("app/{$fullPath}");

        $dir = dirname($tempPath);
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }

        imagepng($image, $tempPath, 6);
        imagedestroy($image);

        Log::warning("Generated placeholder image for scene {$sceneNumber}");

        return $fullPath;
    }

    /**
     * Batch generate multiple images
     *
     * @param array $scenes
     * @param string $outputPath
     * @return array Paths to generated images
     */
    public function batchGenerateImages(array $scenes, string $outputPath): array
    {
        $imagePaths = [];

        foreach ($scenes as $scene) {
            try {
                $sceneNumber = $scene['scene_number'];
                $visualDescription = $scene['visual_description'];

                $imagePath = $this->generateWhiteboardImage(
                    $visualDescription,
                    $sceneNumber,
                    $outputPath
                );

                $imagePaths[$sceneNumber] = $imagePath;

                // Add small delay to avoid rate limiting
                usleep(500000); // 0.5 seconds

            } catch (Exception $e) {
                Log::error("Failed to generate image for scene {$scene['scene_number']}", [
                    'error' => $e->getMessage(),
                ]);

                // Generate placeholder on failure
                $imagePaths[$scene['scene_number']] = $this->generatePlaceholderImage(
                    $scene['scene_number'],
                    $outputPath,
                    $scene['visual_description']
                );
            }
        }

        return $imagePaths;
    }
}
