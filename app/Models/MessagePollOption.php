<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MessagePollOption extends Model
{
    protected $fillable = [
        'poll_id',
        'option_text',
        'vote_count',
    ];

    protected $casts = [
        'poll_id' => 'integer',
        'vote_count' => 'integer',
    ];

    /**
     * Get the poll this option belongs to.
     */
    public function poll(): BelongsTo
    {
        return $this->belongsTo(MessagePoll::class, 'poll_id');
    }

    /**
     * Get all votes for this option.
     */
    public function votes(): HasMany
    {
        return $this->hasMany(MessagePollVote::class, 'option_id');
    }

    /**
     * Check if a user has voted for this option.
     */
    public function hasUserVoted(int $userId): bool
    {
        return $this->votes()->where('user_id', $userId)->exists();
    }
}

