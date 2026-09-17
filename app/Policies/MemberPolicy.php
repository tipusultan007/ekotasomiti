<?php

namespace App\Policies;

use App\Models\User;

class MemberPolicy extends PermissionPolicy
{
    protected function permission(User $user): bool
    {
        return $user->can('manage members') || $user->can('view members');
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
        return $user->can('manage members');
    }

    public function update(User $user, mixed $model): bool
    {
        if (! $user->can('manage members')) {
            return false;
        }

        if ($user->isFieldOfficer()) {
            return in_array($model->area_id, $user->officerAreaIds());
        }

        return true;
    }

    public function delete(User $user, mixed $model): bool
    {
        if (! $user->can('manage members')) {
            return false;
        }

        if ($user->isFieldOfficer()) {
            return in_array($model->area_id, $user->officerAreaIds());
        }

        return true;
    }
}