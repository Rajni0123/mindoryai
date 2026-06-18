<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsageLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action_type',
        'ai_model',
        'tokens_consumed',
        'credits_consumed',
        'ip_address',
        'request_data',
        'response_data',
        'status',
    ];

    protected $casts = [
        'request_data' => 'array',
        'response_data' => 'array',
    ];

    /**
     * Relationship with User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Log usage
     */
    public static function logUsage($userId, $actionType, $data = [])
    {
        return self::create([
            'user_id' => $userId,
            'action_type' => $actionType,
            'ai_model' => $data['ai_model'] ?? null,
            'tokens_consumed' => $data['tokens_consumed'] ?? 0,
            'credits_consumed' => $data['credits_consumed'] ?? 0,
            'ip_address' => $data['ip_address'] ?? request()->ip(),
            'request_data' => $data['request_data'] ?? null,
            'response_data' => $data['response_data'] ?? null,
            'status' => $data['status'] ?? 'success',
        ]);
    }

    /**
     * Get usage stats for a user
     */
    public static function getUserStats($userId)
    {
        return [
            'total_api_calls' => self::where('user_id', $userId)
                ->where('action_type', 'api_call')
                ->count(),
            'total_messages' => self::where('user_id', $userId)
                ->where('action_type', 'message')
                ->count(),
            'total_image_uploads' => self::where('user_id', $userId)
                ->where('action_type', 'image_upload')
                ->count(),
            'total_image_generations' => self::where('user_id', $userId)
                ->where('action_type', 'image_generation')
                ->count(),
            'total_tokens_consumed' => self::where('user_id', $userId)
                ->sum('tokens_consumed'),
            'total_credits_consumed' => self::where('user_id', $userId)
                ->sum('credits_consumed'),
        ];
    }
}
