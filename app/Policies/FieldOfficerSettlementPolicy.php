<?php

namespace App\Policies;

use App\Models\User;

class FieldOfficerSettlementPolicy extends PermissionPolicy
{
    protected function permission(User $user): bool
    {
        return $user->can('manage settlements');
    }
}