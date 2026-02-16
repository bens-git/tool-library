<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id',
        'path',
        'created_by',
    ];


    /**
     * Get the full public URL for the image.
     *
     * @return string|null
     */
    public function getUrlAttribute(): ?string
    {
        return $this->path
            ? asset('storage/' . $this->path)
            : null;
    }
}
