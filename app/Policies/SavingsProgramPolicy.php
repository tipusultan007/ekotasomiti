<?php

namespace App\Policies;

use App\Models\User;

class SavingsProgramPolicy extends PermissionPolicy
{
    protected function permission(User $user): bool
    {
        return $user->can('manage savings programs');
    }
}