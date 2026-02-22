<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    protected $fillable = [
        'conversation_id',
        'user_id',
        'body',
        'is_system_message',
    ];

    protected $casts = [
        'conversation_id' => 'integer',
        'user_id' => 'integer',
        'is_system_message' => 'boolean',
    ];

    /**
     * Get the conversation this message belongs to.
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Get the user who sent this message.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get read statuses for this message.
     */
    public function reads(): HasMany
    {
        return $this->hasMany(MessageRead::class);
    }

    /**
     * Check if a specific user has read this message.
     */
    public function isReadBy(int $userId): bool
    {
        return $this->reads()->where('user_id', $userId)->exists();
    }

    /**
     * Mark this message as read by a user.
     */
    public function markAsReadBy(int $userId): void
    {
        if (!$this->isReadBy($userId)) {
            MessageRead::create([
                'message_id' => $this->id,
                'user_id' => $userId,
            ]);
        }
    }
}

