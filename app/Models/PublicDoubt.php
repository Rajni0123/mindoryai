<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PublicDoubt extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'student_id',
        'topper_id',
        'subject',
        'topic',
        'question',
        'answer',
        'image_url',
        'upvotes',
        'views',
        'is_featured',
        'is_approved',
        'tags',
    ];

    protected $casts = [
        'upvotes' => 'integer',
        'views' => 'integer',
        'is_featured' => 'boolean',
        'is_approved' => 'boolean',
        'tags' => 'array',
    ];

    public function conversation()
    {
        return $this->belongsTo(ChatConversation::class, 'conversation_id');
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function topper()
    {
        return $this->belongsTo(User::class, 'topper_id');
    }

    /**
     * Increment views
     */
    public function incrementViews(): void
    {
        $this->increment('views');
    }

    /**
     * Upvote the doubt
     */
    public function upvote(): void
    {
        $this->increment('upvotes');
    }

    /**
     * Scope for approved doubts
     */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    /**
     * Scope for featured doubts
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope for subject filter
     */
    public function scopeBySubject($query, string $subject)
    {
        return $query->where('subject', $subject);
    }

    /**
     * Scope for popular doubts (sorted by upvotes)
     */
    public function scopePopular($query)
    {
        return $query->orderByDesc('upvotes');
    }

    /**
     * Scope for trending doubts (recent + views)
     */
    public function scopeTrending($query)
    {
        return $query->where('created_at', '>=', now()->subDays(7))
                     ->orderByDesc('views');
    }

    /**
     * Search doubts
     */
    public function scopeSearch($query, string $term)
    {
        return $query->whereRaw('MATCH(question, answer) AGAINST(? IN NATURAL LANGUAGE MODE)', [$term]);
    }

    /**
     * Create public doubt from conversation
     */
    public static function createFromConversation(ChatConversation $conversation): ?self
    {
        // Get the first student message as question
        $questionMessage = $conversation->messages()
            ->where('sender_type', 'student')
            ->orderBy('created_at')
            ->first();

        // Get the first topper response as answer
        $answerMessage = $conversation->messages()
            ->where('sender_type', 'topper')
            ->orderBy('created_at')
            ->first();

        if (!$questionMessage || !$answerMessage) {
            return null;
        }

        return static::create([
            'conversation_id' => $conversation->id,
            'student_id' => $conversation->student_id,
            'topper_id' => $conversation->topper_id,
            'subject' => $conversation->subject,
            'question' => $questionMessage->content,
            'answer' => $answerMessage->content,
            'image_url' => $questionMessage->image_url,
        ]);
    }
}
