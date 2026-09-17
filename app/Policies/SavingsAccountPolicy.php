<?php

namespace App\Policies;

use App\Models\User;

class SavingsAccountPolicy extends PermissionPolicy
{
    protected function permission(User $user): bool
    {
        return $user->can('manage savings accounts') || $user->can('make deposits');
    }

    public function view(User $user, mixed $model): bool
    {
        if (! $this->permission($user)) {
            return false;
        }

        if ($user->isFieldOfficer()) {
            return in_array($model->area_id, $user->officerAreaIds());
        }

        return true;
    }

    public function update(User $user, mixed $model): bool
    {
        if (! $user->can('manage savings accounts')) {
            return false;
        }

        if ($user->isFieldOfficer()) {
            return in_array($model->area_id, $user->officerAreaIds());
        }

        return true;
    }

    public function delete(User $user, mixed $model): bool
    {
        if (! $user->can('manage savings accounts')) {
            return false;
        }

        if ($user->isFieldOfficer()) {
            return in_array($model->area_id, $user->officerAreaIds());
        }

        return true;
    }
}