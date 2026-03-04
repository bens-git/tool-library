<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\ItemAccessValue;
use App\Models\CreditVote;

/**
 * Item Model
 *
 * @property int $id
 * @property string|null $code
 * @property int|null $archetype_id
 * @property string|null $name
 * @property string|null $description
 * @property int $owned_by
 * @property float|null $purchase_value
 * @property string|null $serial
 * @property string|null $purchased_at
 * @property string|null $manufactured_at
 * @property string|null $thumbnail_path
 * @property string $created_at
 * @property string $updated_at
 */
class Item extends Model
{
    protected $table = 'items'; // Specify the table name if it's not following Laravel conventions
    protected $fillable = [
        'archetype_id',
        'name',
        'description',
        'owned_by',
        'purchase_value',
        'serial',
        'purchased_at',
        'manufactured_at',
        'thumbnail_path',
    ];

    /**
     * Get the full public URL for the thumbnail image.
     */
    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->thumbnail_path
            ? asset('storage/' . $this->thumbnail_path)
            : null;
    }

    /**
     * owner
     *
     * @return BelongsTo
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owned_by');
    }


    /**
     * archetype
     *
     * @return BelongsTo
     */
    public function archetype(): BelongsTo
    {
        return $this->belongsTo(Archetype::class);
    }

    /**
     * rentals
     *
     * @return HasMany
     */
    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class);
    }

    /**
     * Get the item's access value (credit cost)
     */
    public function accessValue()
    {
        return $this->hasOne(ItemAccessValue::class);
    }

    /**
     * Get credit votes for this item
     */
    public function creditVotes()
    {
        return $this->hasMany(CreditVote::class);
    }

    /**
     * Get the current credit rate for this item
     */
    public function getCurrentCreditRate(): float
    {
        return $this->accessValue->current_daily_rate ?? ItemAccessValue::DEFAULT_DAILY_RATE;
    }

    /**
     * Calculate credit cost for a rental period
     */
    public function calculateCreditCost(int $days): float
    {
        $rate = $this->getCurrentCreditRate();
        return $rate * $days;
    }
}
