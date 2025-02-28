<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArchetypeImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'archetype_id',
        'path',
        'created_by',
    ];
}