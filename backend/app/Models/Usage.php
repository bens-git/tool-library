<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Usage extends Model
{
    protected $table = 'usages'; // Specify the table name if it's not following Laravel conventions
    protected $fillable = ['name', 'created_by']; // List the fields that can be mass-assigned

    public function types()
    {
        return $this->belongsToMany(Type::class);
    }
}
