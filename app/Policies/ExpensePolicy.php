<?php

namespace App\Policies;

use App\Models\User;

class ExpensePolicy extends PermissionPolicy
{
    protected function permission(User $user): bool
    {
        return $user->can('manage expenses');
    }
}