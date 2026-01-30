<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiModel extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'provider',
        'model_identifier',
        'api_key',
        'api_url',
        'description',
        'icon',
        'color',
        'is_active',
        'supports_vision',
        'max_tokens',
        'temperature',
        'order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'supports_vision' => 'boolean',
        'temperature' => 'float',  // Changed from 'decimal:2' to 'float' to fix AI API compatibility
        'max_tokens' => 'integer',
    ];

    /**
     * Get only active AI models
     */
    public static function active()
    {
        // Return all active models - API key validation happens at runtime
        return self::where('is_active', true)
                   ->orderBy('order')
                   ->get();
    }

    /**
     * Get AI models that support vision
     */
    public static function visionEnabled()
    {
        return self::where('is_active', true)
                   ->where('supports_vision', true)
                   ->orderBy('order')
                   ->get();
    }
}
