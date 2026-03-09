<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Usage extends Model
{
    use HasFactory;

    protected $fillable = [
        'used_by',
        'item_id',
        'used_at',
        'status',
    ];

    /**
     * Item
     *
     * @return BelongsTo
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * User
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rented_by');
    }

    /**
     * renter
     *
     * @return BelongsTo
     */
    public function renter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rented_by');
    }

    /**
     * Get the conversation for this usage (if exists).
     */
    public function conversation():HasOne
    {
        return $this->hasOne(Conversation::class);
    }

    /**
     * Get the item owner.
     */
    public function owner(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->item->owner();
    }
}
