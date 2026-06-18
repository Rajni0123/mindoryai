<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DynamicAppConfig extends Model
{
    protected $fillable = [
        'key',
        'type',
        'value',
        'label',
        'category',
        'description',
        'is_public',
        'version',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'version' => 'integer',
    ];

    /**
     * Get config value by key
     */
    public static function getValue(string $key, $default = null)
    {
        $config = static::where('key', $key)->first();

        if (!$config) {
            return $default;
        }

        return static::decodeValue($config->value, $config->type, $default);
    }

    /**
     * Set config value by key
     */
    public static function setValue(string $key, $value, string $type = 'string', bool $incrementVersion = true): self
    {
        $config = static::where('key', $key)->first();

        $encodedValue = static::encodeValue($value, $type);

        if ($config) {
            $config->value = $encodedValue;
            $config->type = $type;
            if ($incrementVersion) {
                $config->increment('version');
            }
            $config->save();
        } else {
            $config = static::create([
                'key' => $key,
                'value' => $encodedValue,
                'type' => $type,
                'label' => ucwords(str_replace('_', ' ', $key)),
                'version' => 1,
            ]);
        }

        return $config;
    }

    /**
     * Decode value based on type
     */
    protected static function decodeValue($value, string $type, $default = null)
    {
        if ($value === null) {
            return $default;
        }

        return match($type) {
            'json', 'array' => json_decode($value, true),
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'number', 'integer' => is_numeric($value) ? (int)$value : $default,
            'float' => is_numeric($value) ? (float)$value : $default,
            default => $value,
        };
    }

    /**
     * Encode value based on type
     */
    protected static function encodeValue($value, string $type): string
    {
        return match($type) {
            'json', 'array' => json_encode($value),
            'boolean' => $value ? '1' : '0',
            'number', 'integer' => (string)(int)$value,
            'float' => (string)(float)$value,
            default => (string)$value,
        };
    }

    /**
     * Scope for category
     */
    public function scopeOfCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for public configs
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Get all configs as array
     */
    public static function getAllConfigs(): array
    {
        return static::all()
            ->map(fn($config) => [
                'key' => $config->key,
                'value' => static::decodeValue($config->value, $config->type),
                'type' => $config->type,
                'label' => $config->label,
                'category' => $config->category,
                'version' => $config->version,
                'updated_at' => $config->updated_at->toIso8601String(),
            ])
            ->keyBy('key')
            ->toArray();
    }
}
