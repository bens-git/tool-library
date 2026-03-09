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
     * usages
     *
     * @return HasMany
     */
    public function usages(): HasMany
    {
        return $this->hasMany(Usage::class);
    }

    /**
     * Get the item's access value (credit cost)
     */
    public function accessValue(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ItemAccessValue::class);
    }

    /**
     * Get credit votes for this item
     */
    public function creditVotes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CreditVote::class);
    }

    /**
     * Get the archetype's access value for this item
     * This returns the flat rate credit value based on the archetype
     */
    public function getArchetypeCreditValue(): float
    {
        if ($this->archetype) {
            return $this->archetype->getCurrentCreditValue();
        }
        
        // Fallback to item's own access value if no archetype
        return $this->getCurrentCreditRate();
    }

    /**
     * Get the current credit rate for this item
     */
    public function getCurrentCreditRate(): float
    {
        return $this->accessValue->current_daily_rate ?? ItemAccessValue::DEFAULT_DAILY_RATE;
    }

    /**
     * Calculate credit cost for a usage (flat rate based on archetype)
     * This is now a flat fee regardless of duration
     */
    public function calculateCreditCost(int $days = 1): float
    {
        // Return flat rate from archetype - duration no longer matters
        return $this->getArchetypeCreditValue();
    }
}
