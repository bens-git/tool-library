<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Archetype extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'created_by', 'notes', 'code', 'resource'];

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
        return $this->hasMany(ArchetypeImage::class, 'archetype_id');
    }
}
