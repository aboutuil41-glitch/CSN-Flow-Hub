<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Task;
use App\Models\File;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'statue',
        'created_by',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'project_users')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function projectFiles() {
    return $this->hasMany(ProjectFile::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function files()
    {
        return $this->hasMany(File::class);
    }

    

}