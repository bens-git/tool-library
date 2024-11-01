<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemUnavailableDate extends Model
{
    use HasFactory;

    protected $table = 'item_unavailable_dates';

    protected $fillable = [
        'item_id',
        'unavailable_date',
    ];

    /**
     * Get the item that the unavailable date belongs to.
     */
    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
