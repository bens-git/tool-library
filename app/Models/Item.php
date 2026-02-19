<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
 * @property int|null $location_id
 * @property string|null $serial
 * @property string|null $purchased_at
 * @property string|null $manufactured_at
 * @property int|null $brand_id
 * @property bool $make_item_unavailable
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
        'location_id',
        'serial',
        'purchased_at',
        'manufactured_at',
        'brand_id',
        'make_item_unavailable'
    ];

    protected $casts = [
        'make_item_unavailable' => 'boolean', // or 'integer' if you prefer
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ItemImage::class, 'item_id');
    }

    /**
     * Get the dates when the item is unavailable.
     */
    public function unavailableDates(): HasMany
    {
        return $this->hasMany(ItemUnavailableDate::class);
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
     * brand
     *
     * @return BelongsTo
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * category
     *
     * @return BelongsTo
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * usage
     *
     * @return BelongsTo
     */
    public function usage(): BelongsTo
    {
        return $this->belongsTo(Usage::class);
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
}
