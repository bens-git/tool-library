<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $fillable = ['name', 'created_by',  'description'];



    public function jobs()
    {
        return $this->belongsToMany(Job::class, 'project_job')
            ->withPivot('order');
    }

    public function creator()
    {
        return $this->belongsTo(User::class)->select('id', 'name');
    }

    public function finalJob()
    {
        return $this->jobs()
            ->orderByDesc('project_job.order')
            ->limit(1);
    }
}
