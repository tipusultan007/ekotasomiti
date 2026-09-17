<?php

namespace App\Policies;

use App\Models\User;

class LoanProductPolicy extends PermissionPolicy
{
    protected function permission(User $user): bool
    {
        return $user->can('manage loan products');
    }
}