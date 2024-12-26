<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Material;

class Job extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'created_by', 'material_id', 'product_id'];

    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    public function product()
    {
        return $this->belongsTo(Material::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class)->select('id', 'name');
    }
}
