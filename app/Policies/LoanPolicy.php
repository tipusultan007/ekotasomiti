<?php

namespace App\Policies;

use App\Models\User;

class LoanPolicy extends PermissionPolicy
{
    protected function permission(User $user): bool
    {
        return $user->can('manage loans')
            || $user->can('approve loans')
            || $user->can('disburse loans')
            || $user->can('collect repayments');
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

    public function create(User $user): bool
    {
        return $user->can('manage loans');
    }

    public function update(User $user, mixed $model): bool
    {
        if (! $user->can('manage loans')) {
            return false;
        }

        if ($user->isFieldOfficer()) {
            return in_array($model->area_id, $user->officerAreaIds());
        }

        return true;
    }

    public function delete(User $user, mixed $model): bool
    {
        if (! $user->can('manage loans')) {
            return false;
        }

        if ($user->isFieldOfficer()) {
            return in_array($model->area_id, $user->officerAreaIds());
        }

        return true;
    }

    public function writeOff(User $user, mixed $model): bool
    {
        if (! $user->can('write off loans')) {
            return false;
        }

        if ($user->isFieldOfficer()) {
            return in_array($model->area_id, $user->officerAreaIds());
        }

        return true;
    }
}