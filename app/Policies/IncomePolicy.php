<?php

namespace App\Policies;

use App\Models\User;

class IncomePolicy extends PermissionPolicy
{
    protected function permission(User $user): bool
    {
        return $user->can('manage income');
    }
}