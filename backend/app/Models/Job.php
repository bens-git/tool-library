<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Archetype;

class Job extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $fillable = ['name', 'created_by', 'base_id', 'product_id', 'component_id', 'tool_id', 'description'];

    public function base()
    {
        return $this->belongsTo(Archetype::class);
    }

    public function component()
    {
        return $this->belongsTo(Archetype::class);
    }

    public function tool()
    {
        return $this->belongsTo(Archetype::class);
    }

    public function product()
    {
        return $this->belongsTo(Archetype::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class)->select('id', 'name');
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class, 'project_job');
    }
}
