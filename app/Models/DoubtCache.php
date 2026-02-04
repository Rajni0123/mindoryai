<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoubtCache extends Model
{
    protected $table = 'doubt_cache';

    protected $fillable = [
        'type',
        'content_hash',
        'question_hash',
        'question_text',
        'subject',
        'solution',
        'tokens_used',
        'usage_count',
        'last_used_at',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
    ];

    /**
     * Find cached solution by content hash and optional question.
     */
    public static function findCached(string $type, string $contentHash, ?string $questionText = null): ?self
    {
        $questionHash = $questionText ? hash('sha256', strtolower(trim($questionText))) : 'none';

        $cached = static::where('type', $type)
            ->where('content_hash', $contentHash)
            ->where('question_hash', $questionHash)
            ->first();

        if ($cached) {
            $cached->increment('usage_count');
            $cached->update(['last_used_at' => now()]);
        }

        return $cached;
    }

    /**
     * Store or update a cached solution.
     */
    public static function storeSolution(
        string $type,
        string $contentHash,
        ?string $questionText,
        ?string $subject,
        string $solution,
        int $tokensUsed = 0
    ): self {
        $questionHash = $questionText ? hash('sha256', strtolower(trim($questionText))) : 'none';

        return static::updateOrCreate(
            [
                'type' => $type,
                'content_hash' => $contentHash,
                'question_hash' => $questionHash,
            ],
            [
                'question_text' => $questionText,
                'subject' => $subject,
                'solution' => $solution,
                'tokens_used' => $tokensUsed,
                'usage_count' => 1,
                'last_used_at' => now(),
            ]
        );
    }
}
