<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Vendor;
use App\Models\User;

class Material extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'url', 'price', 'modular','vendor_id','created_by'];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class)->select('id', 'name');
    }

    
  
}
