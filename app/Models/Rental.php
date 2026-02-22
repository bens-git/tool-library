<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rental extends Model
{
    use HasFactory;

    protected $fillable = [
        'rented_by',
        'item_id',
        'rented_at',
        'status',
        'renter_punctuality',
        'owner_punctuality',
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
     * Get the conversation for this rental (if exists).
     */
    public function conversation()
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
