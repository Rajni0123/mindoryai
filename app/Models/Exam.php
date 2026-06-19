<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'category',
        'level',
        'subjects',
        'config',
        'icon',
        'is_active',
        'order',
    ];

    protected $casts = [
        'subjects' => 'array',
        'config' => 'array',
        'is_active' => 'boolean',
    ];

    public function questions()
    {
        return $this->hasMany(ExamQuestion::class);
    }

    public function mockTests()
    {
        return $this->hasMany(MockTest::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public static function getBySlug(string $slug)
    {
        return self::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();
    }

    public function getAvailableYears()
    {
        return $this->questions()
            ->whereNotNull('year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');
    }

    public function getAvailableSubjects()
    {
        return $this->questions()
            ->whereNotNull('subject')
            ->distinct()
            ->pluck('subject');
    }

    public function getQuestionCount()
    {
        return $this->questions()->where('is_active', true)->count();
    }
}
