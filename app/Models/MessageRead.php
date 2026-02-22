<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageRead extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'message_id',
        'user_id',
        'read_at',
    ];

    protected $casts = [
        'message_id' => 'integer',
        'user_id' => 'integer',
        'read_at' => 'datetime',
    ];

    /**
     * Get the message this read status belongs to.
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    /**
     * Get the user who read the message.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

