<?php

namespace App\Policies;

use App\Models\User;

class ReportPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    public function view(User $user): bool
    {
        return in_array($user->role, ['admin', 'team_leader']);
    }

    public function export(User $user): bool
    {
        return $this->view($user);
    }
}
