<?php

namespace App\Policies;

use App\Models\User;

abstract class PermissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->permission($user);
    }

    public function view(User $user, mixed $model): bool
    {
        return $this->permission($user);
    }

    public function create(User $user): bool
    {
        return $this->permission($user);
    }

    public function update(User $user, mixed $model): bool
    {
        return $this->permission($user);
    }

    public function delete(User $user, mixed $model): bool
    {
        return $this->permission($user);
    }

    public function restore(User $user, mixed $model): bool
    {
        return $this->permission($user);
    }

    public function forceDelete(User $user, mixed $model): bool
    {
        return $this->permission($user);
    }

    abstract protected function permission(User $user): bool;
}