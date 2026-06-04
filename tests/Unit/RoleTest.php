<?php

use App\Enums\Permission;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('reports whether it holds a permission', function () {
    $admin = Role::factory()->create(['name' => 'admin', 'permissions' => Permission::cases()]);
    $member = Role::factory()->create(['name' => 'member', 'permissions' => []]);

    expect($admin->hasPermission(Permission::UsersManage))->toBeTrue()
        ->and($member->hasPermission(Permission::UsersManage))->toBeFalse();
});
