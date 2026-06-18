<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AiSystemPrompt extends Model
{
    protected $fillable = [
        'feature',
        'name',
        'prompt',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * NO-MARKDOWN prefix that gets added to ALL prompts
     */
    public const NO_MARKDOWN_PREFIX = "CRITICAL FORMATTING RULE - MUST FOLLOW:
You must NOT use any markdown formatting in your response.

FORBIDDEN (NEVER USE):
× ** for bold
× * for bullets or italic
× # or ## for headings
× ``` for code blocks

INSTEAD USE:
✓ CAPITAL LETTERS for headings
✓ • or - for bullet points
✓ 1. 2. 3. for numbered lists
✓ Plain text only

";

    /**
     * Get system prompt for a specific feature
     * Automatically prepends NO-MARKDOWN instructions
     */
    public static function getPromptFor(string $feature): ?string
    {
        return Cache::remember("ai_prompt_{$feature}_v2", 3600, function () use ($feature) {
            $prompt = self::where('feature', $feature)
                ->where('is_active', true)
                ->first();

            if (!$prompt?->prompt) {
                return null;
            }

            // Prepend no-markdown instructions to all prompts
            return self::NO_MARKDOWN_PREFIX . $prompt->prompt;
        });
    }

    /**
     * Clear prompt cache
     */
    public static function clearCache(): void
    {
        $features = ['chat', 'quiz', 'whiteboard', 'image_generation'];
        foreach ($features as $feature) {
            Cache::forget("ai_prompt_{$feature}");
        }
    }
}
