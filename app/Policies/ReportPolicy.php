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

    public function generateReport(User $user): bool
    {
        return in_array($user->role, ['admin', 'team_leader']);
    }

    public function exportReport(User $user): bool
    {
        return $this->generateReport($user);
    }
}
