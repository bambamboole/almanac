<?php

use App\Enums\Permission;
use App\Models\Role;
use App\Models\User;

it('assigns the member role on creation when none is set', function () {
    $user = User::factory()->create();

    expect($user->role)->not->toBeNull()
        ->and($user->role->name)->toBe('member')
        ->and($user->hasPermission(Permission::UsersManage))->toBeFalse();
});

it('reports admin permissions through its role', function () {
    $admin = Role::factory()->create(['name' => 'admin', 'permissions' => Permission::cases()]);
    $user = User::factory()->create(['role_id' => $admin->id]);

    expect($user->hasPermission(Permission::UsersManage))->toBeTrue();
});
