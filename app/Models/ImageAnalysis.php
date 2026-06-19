<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImageAnalysis extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'image_path',
        'ai_response',
        'ip_address',
        'is_deleted',
        'deleted_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_deleted' => 'boolean',
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Mark image as deleted
     *
     * @return void
     */
    public function markAsDeleted(): void
    {
        $this->update([
            'is_deleted' => true,
            'deleted_at' => now(),
        ]);
    }
}
