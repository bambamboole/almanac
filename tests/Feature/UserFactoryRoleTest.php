<?php

use App\Enums\Permission;
use App\Models\User;

it('creates an admin user via the admin state', function () {
    $user = User::factory()->admin()->create();

    expect($user->role->name)->toBe('admin')
        ->and($user->hasPermission(Permission::UsersManage))->toBeTrue()
        ->and($user->hasPermission(Permission::CollectionsManage))->toBeTrue();
});
