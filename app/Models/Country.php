<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Country extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'created_by'];


    public function creator()
    {
        return $this->belongsTo(User::class)->select('id', 'name');
    }

    
  
}
