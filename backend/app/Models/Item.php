<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $table = 'items'; // Specify the table name if it's not following Laravel conventions
    protected $fillable = ['name', 'description', 'type_id', 'owned_by', 'purchase_value', 'location_id', 'serial', 'purchased_at', 'manufactured_at', 'brand_id']; // List the fields that can be mass-assigned

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function images()
    {
        return $this->hasMany(ItemImage::class, 'item_id');
    }

    /**
     * Get the dates when the item is unavailable.
     */
    public function unavailableDates()
    {
        return $this->hasMany(ItemUnavailableDate::class);
    }


    public function owner()
    {
        return $this->belongsTo(User::class, 'owned_by');
    }
}
