<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    /**
     * Can view any task list – Admins and Team Leaders.
     */
    public function viewAny(User $user)
    {
        return in_array($user->role, ['admin', 'team_leader']);
    }

    /**
     * Can view a specific task.
     */
    public function view(User $user, Task $task)
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'team_leader') {
            return $task->project->team_leader_id === $user->id;
        }

        if ($user->role === 'team_member') {
            return $task->assigned_to === $user->id;
        }

        return false;
    }

    /**
     * Can create a task — Admin or Team Leader (in their own project).
     */
    public function create(User $user, Project $project)
    {
        return $user->role === 'admin' ||
            ($user->role === 'team_leader' && $project->team_leader_id === $user->id);
    }

    /**
     * Can update task — Admin, Team Leader (of the project), or Assigned Team Member.
     */
    public function update(User $user, Task $task)
    {
        // Admin and Team Leader can update anything
        if (in_array($user->role, ['admin', 'team_leader'])) {
            return true;
        }

        // Team Member can only update their own assigned task's status
        if ($user->role === 'team_member' && $task->assigned_to === $user->id) {
            return true;
        }
        return false;
    }


    /**
     * Can delete task — Admin or Team Leader (of the project).
     */
    public function delete(User $user, Task $task)
    {
        return $user->role === 'admin' ||
            ($user->role === 'team_leader' && $task->project->team_leader_id === $user->id);
    }

    /**
     * Can assign task — Admin or Team Leader (of the project).
     */
    public function assign(User $user, Task $task)
    {
        return $user->role === 'admin' ||
            ($user->role === 'team_leader' && $task->project->team_leader_id === $user->id);
    }

    /**
     * Can unassign task — Same as assign.
     */
    public function unassign(User $user, Task $task)
    {
        return $this->assign($user, $task);
    }

    /**
     * Can mark a task as complete — Admin, Team Leader (project owner), or Assigned Member.
     */
    public function markAsComplete(User $user, Task $task)
    {
        return $this->update($user, $task);
    }

}
