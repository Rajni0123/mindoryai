<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'message',
        'type',
        'is_read',
        'is_global',
        'read_at',
        'created_by',
        'scheduled_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'is_global' => 'boolean',
        'read_at' => 'datetime',
        'scheduled_at' => 'datetime',
    ];

    /**
     * Relationship with user (recipient)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship with admin (sender)
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope for unread notifications
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeUnreadForUser($query, int $userId)
    {
        $readGlobalIds = NotificationUserRead::where('user_id', $userId)->pluck('notification_id');

        return $query->where(function ($q) use ($userId, $readGlobalIds) {
            $q->where(function ($personal) use ($userId) {
                $personal->where('user_id', $userId)->where('is_read', false);
            })->orWhere(function ($global) use ($readGlobalIds) {
                $global->where('is_global', true);
                if ($readGlobalIds->isNotEmpty()) {
                    $global->whereNotIn('id', $readGlobalIds);
                }
            });
        });
    }

    /**
     * Scope for read notifications
     */
    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    /**
     * Scope for user-specific notifications
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where(function($q) use ($userId) {
            $q->where('user_id', $userId)
              ->orWhere('is_global', true);
        });
    }

    /**
     * Mark notification as read for a specific user (handles global notifications safely).
     */
    public function markAsReadForUser(int $userId): void
    {
        if ($this->is_global) {
            NotificationUserRead::updateOrCreate(
                ['user_id' => $userId, 'notification_id' => $this->id],
                ['read_at' => now()]
            );

            return;
        }

        if ((int) $this->user_id !== $userId) {
            return;
        }

        $this->markAsRead();
    }

    public static function markAllAsReadForUser(int $userId): void
    {
        static::where('user_id', $userId)
            ->unread()
            ->update(['is_read' => true, 'read_at' => now()]);

        $globalIds = static::where('is_global', true)->pluck('id');
        $now = now();

        foreach ($globalIds as $notificationId) {
            NotificationUserRead::updateOrCreate(
                ['user_id' => $userId, 'notification_id' => $notificationId],
                ['read_at' => $now]
            );
        }
    }

    public function isReadByUser(int $userId): bool
    {
        if ($this->is_global) {
            return NotificationUserRead::where('user_id', $userId)
                ->where('notification_id', $this->id)
                ->exists();
        }

        return (bool) $this->is_read;
    }

    /**
     * Mark notification as read
     */
    public function markAsRead()
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Mark notification as unread
     */
    public function markAsUnread()
    {
        $this->update([
            'is_read' => false,
            'read_at' => null,
        ]);
    }
}
