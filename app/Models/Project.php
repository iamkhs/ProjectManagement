<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'team_leader_id',
    ];


    public function teamLeader()
    {
        return $this->belongsTo(User::class, 'team_leader_id');
    }

    public function teamMembers()
    {
        return $this->belongsToMany(User::class, 'project_members');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

}
