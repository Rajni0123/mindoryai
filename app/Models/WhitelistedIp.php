<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhitelistedIp extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'ip_address',
        'description',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Check if an IP address is whitelisted
     *
     * @param string $ipAddress
     * @return bool
     */
    public static function isWhitelisted(string $ipAddress): bool
    {
        return self::where('ip_address', $ipAddress)
            ->where('is_active', true)
            ->exists();
    }
}
