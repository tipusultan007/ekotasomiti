<?php

namespace App\Policies;

use App\Models\User;

class BankAccountPolicy extends PermissionPolicy
{
    protected function permission(User $user): bool
    {
        return $user->can('manage bank accounts');
    }
}