<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Type extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'created_by', 'notes', 'code'];

    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }

    public function usages()
    {
        return $this->belongsToMany(Usage::class);
    }

    
    public function images()
    {
        return $this->hasMany(TypeImage::class, 'type_id');
    }
}
