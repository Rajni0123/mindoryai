<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiUsageTracking extends Model
{
    protected $table = 'ai_usage_tracking';

    protected $fillable = [
        'user_id',
        'feature',
        'model_name',
        'provider',
        'input_tokens',
        'output_tokens',
        'total_tokens',
        'estimated_cost',
        'request_type',
        'metadata',
    ];

    protected $casts = [
        'estimated_cost' => 'decimal:6',
        'metadata' => 'array',
    ];

    /**
     * Get user relationship
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Track AI usage
     */
    public static function track(
        string $feature,
        string $modelName,
        int $inputTokens,
        int $outputTokens,
        ?int $userId = null,
        ?string $provider = 'google',
        ?array $metadata = null
    ): self {
        $totalTokens = $inputTokens + $outputTokens;
        $cost = self::estimateCost($modelName, $inputTokens, $outputTokens);

        return self::create([
            'user_id' => $userId,
            'feature' => $feature,
            'model_name' => $modelName,
            'provider' => $provider,
            'input_tokens' => $inputTokens,
            'output_tokens' => $outputTokens,
            'total_tokens' => $totalTokens,
            'estimated_cost' => $cost,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Estimate cost based on model and tokens
     */
    private static function estimateCost(string $modelName, int $inputTokens, int $outputTokens): float
    {
        // Pricing per 1M tokens (USD)
        $pricing = [
            'gemini-1.5-flash' => ['input' => 0.075, 'output' => 0.30],
            'gemini-1.5-pro' => ['input' => 1.25, 'output' => 5.00],
            'gemini-2.0-flash' => ['input' => 0.10, 'output' => 0.40],
            'gpt-4o' => ['input' => 2.50, 'output' => 10.00],
            'gpt-4o-mini' => ['input' => 0.15, 'output' => 0.60],
        ];

        $rates = $pricing[$modelName] ?? ['input' => 0.10, 'output' => 0.40];

        $inputCost = ($inputTokens / 1000000) * $rates['input'];
        $outputCost = ($outputTokens / 1000000) * $rates['output'];

        return round($inputCost + $outputCost, 6);
    }

    /**
     * Get usage statistics for a feature
     */
    public static function getFeatureStats(string $feature, int $days = 30): array
    {
        $from = now()->subDays($days);

        $stats = self::where('feature', $feature)
            ->where('created_at', '>=', $from)
            ->selectRaw('
                COUNT(*) as total_requests,
                SUM(input_tokens) as total_input_tokens,
                SUM(output_tokens) as total_output_tokens,
                SUM(total_tokens) as total_tokens,
                SUM(estimated_cost) as total_cost
            ')
            ->first();

        return [
            'total_requests' => $stats->total_requests ?? 0,
            'total_input_tokens' => $stats->total_input_tokens ?? 0,
            'total_output_tokens' => $stats->total_output_tokens ?? 0,
            'total_tokens' => $stats->total_tokens ?? 0,
            'total_cost' => $stats->total_cost ?? 0,
        ];
    }
}
