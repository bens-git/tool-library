<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Conversation extends Model
{
    protected $fillable = [
        'type',
        'usage_id',
    ];

    protected $casts = [
        'type' => 'string',
        'usage_id' => 'integer',
    ];

    /**
     * Get the usage associated with this conversation (for private usages).
     */
    public function usage(): BelongsTo
    {
        return $this->belongsTo(Usage::class);
    }

    /**
     * Get the participants in this conversation.
     */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_participants')
            ->withTimestamps();
    }

    /**
     * Get all messages in this conversation.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at', 'asc');
    }

    /**
     * Get the latest message in this conversation.
     */
    public function latestMessage(): HasMany
    {
        return $this->hasMany(Message::class)->latest()->take(1);
    }

    /**
     * Check if a user is a participant in this conversation.
     */
    public function hasParticipant(int $userId): bool
    {
        return $this->participants()->where('user_id', $userId)->exists();
    }

    /**
     * Get the other participant in a private conversation.
     */
    public function getOtherParticipant(int $currentUserId): ?User
    {
        /** @var User|null $user */
        $user = $this->participants()
            ->where('user_id', '!=', $currentUserId)
            ->first();

        return $user;
    }

    
    /**
     * Scope to filter public conversations.
     */
    public function scopePublic($query)
    {
        return $query->where('type', 'public');
    }

    /**
     * Scope to filter private conversations.
     */
    public function scopePrivate($query)
    {
        return $query->where('type', 'private');
    }

    /**
     * Scope to get conversations for a specific user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->whereHas('participants', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        });
    }
}
