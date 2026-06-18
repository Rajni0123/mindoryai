<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class UserFeatureUsage extends Model
{
    use HasFactory;

    protected $table = 'user_feature_usage';

    protected $fillable = [
        'user_id',
        'feature_slug',
        'usage_date',
        'usage_count',
    ];

    protected $casts = [
        'usage_date' => 'date',
        'usage_count' => 'integer',
    ];

    /**
     * Get the user that owns this usage record.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to filter by feature slug.
     */
    public function scopeForFeature($query, string $featureSlug)
    {
        return $query->where('feature_slug', $featureSlug);
    }

    /**
     * Scope to filter by date.
     */
    public function scopeForDate($query, $date)
    {
        return $query->where('usage_date', Carbon::parse($date)->toDateString());
    }

    /**
     * Scope to get today's usage.
     */
    public function scopeToday($query)
    {
        return $query->where('usage_date', Carbon::today()->toDateString());
    }

    /**
     * Scope to get this month's usage.
     */
    public function scopeThisMonth($query)
    {
        return $query->whereYear('usage_date', Carbon::now()->year)
            ->whereMonth('usage_date', Carbon::now()->month);
    }

    /**
     * Increment usage count.
     */
    public function incrementUsage(int $count = 1): bool
    {
        $this->usage_count += $count;
        return $this->save();
    }

    /**
     * Get or create usage record for today.
     */
    public static function getOrCreateForToday(int $userId, string $featureSlug): self
    {
        return static::firstOrCreate(
            [
                'user_id' => $userId,
                'feature_slug' => $featureSlug,
                'usage_date' => Carbon::today()->toDateString(),
            ],
            [
                'usage_count' => 0,
            ]
        );
    }

    /**
     * Get total usage for a feature this month.
     */
    public static function getMonthlyUsage(int $userId, string $featureSlug): int
    {
        return static::where('user_id', $userId)
            ->where('feature_slug', $featureSlug)
            ->thisMonth()
            ->sum('usage_count');
    }

    /**
     * Get today's usage for a feature.
     */
    public static function getTodayUsage(int $userId, string $featureSlug): int
    {
        $usage = static::where('user_id', $userId)
            ->where('feature_slug', $featureSlug)
            ->today()
            ->first();

        return $usage ? $usage->usage_count : 0;
    }
}
