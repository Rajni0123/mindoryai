<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StorageSetting extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'driver',
        'key',
        'secret',
        'region',
        'bucket',
        'endpoint',
        'cdn_url',
        'url_prefix',
        'is_active',
        'max_file_size',
        'allowed_extensions',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'max_file_size' => 'integer',
        'allowed_extensions' => 'array',
    ];

    /**
     * Get the active storage configuration
     */
    public static function getActive(): ?self
    {
        return static::where('is_active', true)->first();
    }

    /**
     * Activate this storage and deactivate all others
     */
    public function activate(): void
    {
        static::where('is_active', true)->update(['is_active' => false]);
        $this->update(['is_active' => true]);
    }

    /**
     * Deactivate this storage
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Check if this storage is the active one
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Get allowed file extensions as array
     */
    public function getAllowedExtensions(): array
    {
        return $this->allowed_extensions ?? [];
    }

    /**
     * Get maximum file size in bytes.
     * Handles both raw MB values (< 1000) and byte values from database.
     */
    public function getMaxFileSize(): int
    {
        $val = $this->max_file_size ?: 25;
        // If value is small (< 1024), it was entered as MB in the admin form
        if ($val < 1024) {
            return $val * 1024 * 1024;
        }
        // Otherwise it's already in bytes
        return $val;
    }
}
