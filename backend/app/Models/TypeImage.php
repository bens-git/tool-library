<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TypeImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'type_id',
        'path',
        'created_by',
    ];
}