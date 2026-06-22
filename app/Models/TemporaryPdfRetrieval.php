<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TemporaryPdfRetrieval extends Model
{
    protected $fillable = [
        'cache_key',
        'source_url',
        'extracted_text',
        'chunks',
        'expires_at',
    ];

    protected $casts = [
        'chunks' => 'array',
        'expires_at' => 'datetime',
    ];

    public function scopeValid($query)
    {
        return $query->where('expires_at', '>', now());
    }
}
