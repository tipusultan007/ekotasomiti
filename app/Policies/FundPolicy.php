<?php

namespace App\Policies;

use App\Models\User;

class FundPolicy extends PermissionPolicy
{
    protected function permission(User $user): bool
    {
        return $user->hasRole('super_admin') || $user->hasRole('manager') || $user->can('manage funds');
    }
}

