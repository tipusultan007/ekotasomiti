<?php

namespace App\Policies;

use App\Models\User;

class SavingsWithdrawalPolicy extends PermissionPolicy
{
    protected function permission(User $user): bool
    {
        return $user->can('manage withdrawals');
    }
}