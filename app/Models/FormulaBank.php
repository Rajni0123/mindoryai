<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormulaBank extends Model
{
    protected $table = 'formula_bank';

    protected $fillable = [
        'content_hash',
        'name',
        'formula_latex',
        'formula_unicode',
        'explanation',
        'subject',
        'chapter',
        'class_level',
        'keywords',
        'hit_count',
        'is_active',
    ];

    protected $casts = [
        'hit_count' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Scope for active entries
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for subject
     */
    public function scopeOfSubject($query, string $subject)
    {
        return $query->where('subject', $subject);
    }

    /**
     * Scope for class level
     */
    public function scopeOfClass($query, string $classLevel)
    {
        return $query->where('class_level', $classLevel);
    }

    /**
     * Get most used formulas
     */
    public function scopeMostUsed($query, int $limit = 10)
    {
        return $query->orderBy('hit_count', 'desc')->limit($limit);
    }

    /**
     * Search by name or keywords
     */
    public function scopeSearch($query, string $searchTerm)
    {
        return $query->where(function ($q) use ($searchTerm) {
            $q->where('name', 'LIKE', "%{$searchTerm}%")
              ->orWhere('keywords', 'LIKE', "%{$searchTerm}%");
        });
    }
}
