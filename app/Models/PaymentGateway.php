<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentGateway extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'is_enabled',
        'api_key',
        'api_secret',
        'merchant_id',
        'webhook_secret',
        'is_test_mode',
        'config',
        'sort_order',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'is_test_mode' => 'boolean',
        'config' => 'array',
    ];

    protected $hidden = [
        'api_secret',
        'webhook_secret',
    ];

    /**
     * Get enabled payment gateways
     */
    public static function getEnabled()
    {
        return static::where('is_enabled', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Check if gateway is configured
     * In test mode without API keys, allows testing with mock responses
     */
    public function isConfigured(): bool
    {
        // If real API keys are provided, gateway is configured
        if (!empty($this->api_key) && !empty($this->api_secret)) {
            return true;
        }

        // Allow test mode without API keys for development/testing
        if ($this->is_test_mode && $this->is_enabled) {
            return true;
        }

        return false;
    }

    /**
     * Check if using mock/demo mode (test mode without real credentials)
     */
    public function isMockMode(): bool
    {
        return $this->is_test_mode && (empty($this->api_key) || empty($this->api_secret));
    }
}
