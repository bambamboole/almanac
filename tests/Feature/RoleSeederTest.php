<?php

use App\Enums\Permission;
use App\Enums\UserRole;
use App\Models\Role;
use Database\Seeders\RoleSeeder;

it('seeds the admin and member roles idempotently', function () {
    (new RoleSeeder)->run();
    (new RoleSeeder)->run();

    expect(Role::query()->count())->toBe(2);

    $admin = Role::query()->where('name', UserRole::Admin->value)->first();
    $member = Role::query()->where('name', UserRole::Member->value)->first();

    expect($admin->hasPermission(Permission::UsersManage))->toBeTrue()
        ->and($admin->permissions)->toHaveCount(count(Permission::cases()))
        ->and($member->permissions)->toBeEmpty();
});
