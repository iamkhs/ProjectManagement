<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'team_leader']);
    }

    public function view(User $user, Project $project): bool
    {
        return $user->role === 'admin'
            || $user->role === 'team_leader'
            || $project->members->contains($user->id);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'team_leader']);
    }

    public function update(User $user, Project $project): bool
    {
        return $user->role === 'admin'
            || ($user->role === 'team_leader' && $project->created_by === $user->id);
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->role === 'admin'
            || ($user->role === 'team_leader' && $project->created_by === $user->id);
    }

    public function assign(User $user, Project $project): bool
    {
        return in_array($user->role, ['admin', 'team_leader']);
    }

    public function unassign(User $user, Project $project): bool
    {
        return in_array($user->role, ['admin', 'team_leader']);
    }
}
