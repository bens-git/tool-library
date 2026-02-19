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
     * Get the location through the item relationship.
     *
     * @return \App\Models\Location|null
     */
    public function getLocationAttribute(): ?\App\Models\Location
    {
        return $this->item?->location;
    }
}
