<?php

namespace App\Actions\Users;

use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;

class UpdateUser
{
    public function handle(User $user, string $name, string $email, UserRole|string $role): User
    {
        $user->name = $name;
        $user->email = $email;
        $user->role_id = Role::forName($role)->id;
        $user->save();

        return $user->refresh();
    }
}
