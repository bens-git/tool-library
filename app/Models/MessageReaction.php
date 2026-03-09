<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageReaction extends Model
{
    protected $fillable = [
        'message_id',
        'user_id',
        'emoji',
    ];

    protected $casts = [
        'message_id' => 'integer',
        'user_id' => 'integer',
    ];

    /**
     * Get the message this reaction belongs to.
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    /**
     * Get the user who added this reaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

