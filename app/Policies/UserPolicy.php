<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Role;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::UsersManage);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::UsersManage);
    }

    public function update(User $user, User $target): bool
    {
        return $user->hasPermission(Permission::UsersManage);
    }

    public function delete(User $user, User $target): bool
    {
        return $user->hasPermission(Permission::UsersManage)
            && $user->isNot($target)
            && ! $this->isLastAdmin($target);
    }

    private function isLastAdmin(User $user): bool
    {
        $adminRole = Role::admin();

        return $user->role_id === $adminRole->id
            && User::query()->where('role_id', $adminRole->id)->count() === 1;
    }
}
