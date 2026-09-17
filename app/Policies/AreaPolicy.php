<?php

namespace App\Policies;

use App\Models\User;

class AreaPolicy extends PermissionPolicy
{
    protected function permission(User $user): bool
    {
        return $user->can('manage areas');
    }
}