<?php

namespace App\Actions\Users;

use App\Actions\Dav\CreateDefaultDavCollections;
use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateUser
{
    public function __construct(private CreateDefaultDavCollections $collections) {}

    public function handle(
        string $name,
        string $email,
        string $password,
        UserRole|string $role = UserRole::Member,
        bool $emailVerified = false,
        bool $createDefaultDavCollections = true,
    ): User {
        return DB::transaction(function () use ($name, $email, $password, $role, $emailVerified, $createDefaultDavCollections): User {
            $user = new User;
            $user->name = $name;
            $user->email = $email;
            $user->password = $password;
            $user->role_id = Role::forName($role)->id;

            if ($emailVerified) {
                $user->email_verified_at = now();
            }

            $user->save();

            if ($createDefaultDavCollections) {
                $this->collections->handle($user);
            }

            return $user->refresh();
        });
    }
}
