<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Archetype extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'created_by', 'notes', 'code', 'resource'];

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function usages(): BelongsToMany
    {
        return $this->belongsToMany(Usage::class);
    }

   
}
