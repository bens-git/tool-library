<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Country;
use App\Models\User;

class Vendor extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'url', 'country_id','created_by'];

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class)->select('id', 'name');
    }

    
  
}
