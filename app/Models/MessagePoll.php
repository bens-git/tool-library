<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MessagePoll extends Model
{
    protected $fillable = [
        'message_id',
        'question',
        'is_multiple_choice',
        'is_closed',
        'expires_at',
    ];

    protected $casts = [
        'message_id' => 'integer',
        'is_multiple_choice' => 'boolean',
        'is_closed' => 'boolean',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the message this poll belongs to.
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    /**
     * Get all options for this poll.
     */
    public function options(): HasMany
    {
        return $this->hasMany(MessagePollOption::class, 'poll_id')->orderBy('id', 'asc');
    }

    /**
     * Get all votes for this poll.
     */
    public function votes(): HasMany
    {
        return $this->hasMany(MessagePollVote::class, 'poll_id');
    }

    /**
     * Check if a user has voted on this poll.
     */
    public function hasUserVoted(int $userId): bool
    {
        return $this->votes()->where('user_id', $userId)->exists();
    }

    /**
     * Get the options a user has voted for.
     */
    public function getUserVotes(int $userId): array
    {
        return $this->votes()
            ->where('user_id', $userId)
            ->pluck('option_id')
            ->toArray();
    }

    /**
     * Get total vote count.
     */
    public function getTotalVotesAttribute(): int
    {
        return $this->votes()->count();
    }

    /**
     * Check if poll has expired.
     */
    public function hasExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if user can vote (poll not closed and not expired).
     */
    public function canVote(int $userId): bool
    {
        return !$this->is_closed && !$this->hasExpired();
    }
}

