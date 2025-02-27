<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResourceArchetypeImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'resource_archetype_id',
        'path',
        'created_by',
    ];
}