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
     * Get system prompt for a specific feature
     */
    public static function getPromptFor(string $feature): ?string
    {
        return Cache::remember("ai_prompt_{$feature}", 3600, function () use ($feature) {
            $prompt = self::where('feature', $feature)
                ->where('is_active', true)
                ->first();

            return $prompt?->prompt;
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
