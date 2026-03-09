<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessagePollVote extends Model
{
    protected $fillable = [
        'poll_id',
        'option_id',
        'user_id',
    ];

    protected $casts = [
        'poll_id' => 'integer',
        'option_id' => 'integer',
        'user_id' => 'integer',
    ];

    /**
     * Get the poll this vote belongs to.
     */
    public function poll(): BelongsTo
    {
        return $this->belongsTo(MessagePoll::class, 'poll_id');
    }

    /**
     * Get the option this vote is for.
     */
    public function option(): BelongsTo
    {
        return $this->belongsTo(MessagePollOption::class, 'option_id');
    }

    /**
     * Get the user who cast this vote.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

