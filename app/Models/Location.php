<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $fillable = [
        'name',
        'email',
        'password',
        'street_address',
        'city',
        'state',
        'country',
        'postal_code',
        'building_name',
        'floor_number',
        'unit_number',
        'latitude',
        'longitude'
    ];

    use HasFactory;
}
