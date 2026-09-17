<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy extends PermissionPolicy
{
    protected function permission(User $user): bool
    {
        return $user->can('manage users');
    }
}