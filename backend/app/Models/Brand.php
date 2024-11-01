<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    protected $table = 'brands'; // Specify the table name if it's not following Laravel conventions
    protected $fillable = ['name', 'created_by']; // List the fields that can be mass-assigned


}
