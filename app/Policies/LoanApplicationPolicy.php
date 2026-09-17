<?php

namespace App\Policies;

use App\Models\User;

class LoanApplicationPolicy extends PermissionPolicy
{
    protected function permission(User $user): bool
    {
        return $user->can('manage loan applications');
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
        return $user->can('manage loan applications');
    }

    public function update(User $user, mixed $model): bool
    {
        if (! $user->can('manage loan applications')) {
            return false;
        }

        if ($user->isFieldOfficer()) {
            return in_array($model->area_id, $user->officerAreaIds());
        }

        return true;
    }

    public function delete(User $user, mixed $model): bool
    {
        if (! $user->can('manage loan applications')) {
            return false;
        }

        if ($user->isFieldOfficer()) {
            return in_array($model->area_id, $user->officerAreaIds());
        }

        return true;
    }
}