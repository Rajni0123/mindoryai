<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserIpWhitelist extends Model
{
    use HasFactory;

    protected $table = 'user_ip_whitelist';

    protected $fillable = [
        'user_id',
        'ip_address',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Relationship with User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if IP is whitelisted for a specific user
     */
    public static function isWhitelistedForUser($userId, $ipAddress)
    {
        return self::where('user_id', $userId)
            ->where('ip_address', $ipAddress)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Get all whitelisted IPs for a user
     */
    public static function getForUser($userId)
    {
        return self::where('user_id', $userId)
            ->where('is_active', true)
            ->pluck('ip_address')
            ->toArray();
    }
}
