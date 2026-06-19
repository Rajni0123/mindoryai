<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    protected $fillable = [
        'name', 'email', 'phone', 'subject', 'message',
        'status', 'admin_reply', 'replied_at', 'replied_by', 'ip_address',
    ];

    protected $casts = [
        'replied_at' => 'datetime',
    ];

    public function repliedByUser()
    {
        return $this->belongsTo(User::class, 'replied_by');
    }

    public function scopeUnread($query)
    {
        return $query->where('status', 'new');
    }

    public function scopeLatest($query)
    {
        return $query->orderByDesc('created_at');
    }
}
