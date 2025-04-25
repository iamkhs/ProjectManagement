<?php

namespace App\Policies;

use App\Models\SubTask;
use App\Models\User;

class SubtaskPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    // Admin and Team Leader can create subtasks
    public function create(User $user)
    {
        return in_array($user->role, ['admin', 'team_leader']);
    }

    // Anyone can view subtasks assigned to them, others only if Admin or Team Leader
    public function view(User $user, SubTask $subTask)
    {
        return $user->role === 'admin' ||
            $user->role === 'team_leader' ||
            $subTask->assigned_to === $user->id;
    }

    // Only Admin and Team Leader can update any part of the subtask
    // Team Member can only update if they're assigned AND only status
    public function update(User $user, SubTask $subTask)
    {
        // Admin and Team Leader can update anything
        if (in_array($user->role, ['admin', 'team_leader'])) {
            return true;
        }

        // Team Member can only update their own assigned task's status
        if ($user->role === 'team_member' && $subTask->assigned_to === $user->id) {
            return true;
        }
        return false;
    }

    /**
     * Can delete task — Admin or Team Leader (of the project).
     */
    public function delete(User $user, SubTask $task)
    {
        return $user->role === 'admin' ||
            ($user->role === 'team_leader' && $task->assigned_by === $user->id);
    }

    // Only assigned user can mark as complete or Admin/Team Leader
    public function markAsComplete(User $user, SubTask $subTask)
    {
        return $this->update($user, $subTask);
    }

    // Admin and Team Leader can assign subtasks
    public function assign(User $user)
    {
        return in_array($user->role, ['admin', 'team_leader']);
    }

    // Admin or Team Leader can unassign
    public function unassign(User $user)
    {
        return $this->assign($user);
    }
}
