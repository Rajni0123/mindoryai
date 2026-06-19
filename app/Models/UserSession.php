<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'device_id',
        'device_name',
        'device_type',
        'ip_address',
        'is_first_login',
        'profile_was_incomplete',
        'login_at',
        'logout_at',
    ];

    protected $casts = [
        'is_first_login' => 'boolean',
        'profile_was_incomplete' => 'boolean',
        'login_at' => 'datetime',
        'logout_at' => 'datetime',
    ];

    /**
     * Relationship: User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Create login session
     */
    public static function logLogin(
        int $userId,
        bool $isFirstLogin = false,
        bool $profileWasIncomplete = false,
        ?string $deviceId = null,
        ?string $deviceName = null,
        ?string $deviceType = null,
        ?string $ipAddress = null
    ): self {
        return self::create([
            'user_id' => $userId,
            'device_id' => $deviceId,
            'device_name' => $deviceName,
            'device_type' => $deviceType,
            'ip_address' => $ipAddress,
            'is_first_login' => $isFirstLogin,
            'profile_was_incomplete' => $profileWasIncomplete,
            'login_at' => now(),
        ]);
    }
}
