<?php

use App\Enums\Permission;
use App\Models\Role;
use App\Models\User;
use Bambamboole\LaravelDav\Models\DavCalendarInstance;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

it('denies a member access to the users index', function () {
    $this->actingAs(User::factory()->create())
        ->get('/users')
        ->assertForbidden();
});

it('lists users for an admin', function () {
    $this->admin->forceFill(['name' => 'A Admin'])->save();
    User::factory()->count(3)->sequence(
        ['name' => 'B Member'],
        ['name' => 'C Member'],
        ['name' => 'D Member'],
    )->create();

    $this->actingAs($this->admin)
        ->get('/users')
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('users/index')
            ->has('users', 4)
            ->where('users.0.role.id', $this->admin->role_id)
            ->where('users.0.role.name', 'admin')
            ->where('users.0.role.permissions', collect(Permission::cases())->map->value->all())
            ->where('auth.user.role.id', $this->admin->role_id)
            ->where('auth.user.role.name', 'admin')
            ->where('auth.user.role.permissions', collect(Permission::cases())->map->value->all()));
});

it('does not serve user management from settings', function () {
    $this->actingAs($this->admin)
        ->get('/settings/users')
        ->assertNotFound();
});

it('creates a user with default collections provisioned', function () {
    $this->actingAs($this->admin)
        ->post('/users', [
            'name' => 'New Person',
            'email' => 'new@example.com',
            'password' => 'password-123',
            'role' => 'member',
        ])
        ->assertRedirect('/users');

    $user = User::query()->where('email', 'new@example.com')->firstOrFail();
    expect($user->role->name)->toBe('member')
        ->and(DavCalendarInstance::query()->where('owner_id', $user->id)->exists())->toBeTrue();
});

it('promotes a user to admin on update', function () {
    $target = User::factory()->create();

    $this->actingAs($this->admin)
        ->patch("/users/{$target->id}", [
            'name' => $target->name,
            'email' => $target->email,
            'role' => 'admin',
        ])
        ->assertRedirect('/users');

    expect($target->fresh()->role->name)->toBe('admin');
});

it('does not demote the last admin', function () {
    $this->actingAs($this->admin)
        ->from('/users')
        ->patch("/users/{$this->admin->id}", [
            'name' => $this->admin->name,
            'email' => $this->admin->email,
            'role' => 'member',
        ])
        ->assertRedirect('/users')
        ->assertSessionHasErrors('role');

    expect($this->admin->fresh()->role->name)->toBe('admin');
});

it('demotes an admin when another admin remains', function () {
    $target = User::factory()->admin()->create();

    $this->actingAs($this->admin)
        ->patch("/users/{$target->id}", [
            'name' => $target->name,
            'email' => $target->email,
            'role' => 'member',
        ])
        ->assertRedirect('/users')
        ->assertSessionHasNoErrors();

    expect($target->fresh()->role->name)->toBe('member');
});

it('deletes a user', function () {
    $target = User::factory()->create();

    $this->actingAs($this->admin)
        ->delete("/users/{$target->id}")
        ->assertRedirect('/users');

    expect(User::query()->whereKey($target->id)->exists())->toBeFalse();
});

it('does not delete the last admin', function () {
    $managerRole = Role::factory()->create([
        'permissions' => [Permission::UsersManage],
    ]);
    $manager = User::factory()->create([
        'role_id' => $managerRole->id,
    ]);

    $this->actingAs($manager)
        ->delete("/users/{$this->admin->id}")
        ->assertForbidden();

    expect(User::query()->whereKey($this->admin->id)->exists())->toBeTrue();
});
