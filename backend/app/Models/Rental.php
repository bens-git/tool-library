<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rental extends Model
{
    use HasFactory;

    protected $fillable = [
        'rented_by',
        'item_id',
        'rented_at',
        'starts_at',
        'ends_at',
        'status',
        'renter_punctuality',
        'owner_punctuality',
    ];

    // If there are relationships, define them here
    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'rented_by');
    }

    public function renter()
    {
        return $this->belongsTo(User::class, 'rented_by');
    }
}
