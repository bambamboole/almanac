<?php

use App\Models\User;

it('lets an admin create a user from the UI', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    $page = visit('/users');

    $page->assertSee('Users')
        ->assertNoJavaScriptErrors()
        ->click('New user')
        ->fill('name', 'Browser Person')
        ->fill('email', 'browser@example.com')
        ->fill('password', 'password-123')
        ->click('[data-testid="role-select-trigger"]')
        ->click('[data-testid="role-option-admin"]')
        ->click('Create user')
        ->assertSee('browser@example.com');

    expect(User::query()->where('email', 'browser@example.com')->firstOrFail()->role->name)->toBe('admin');
});

it('lets an admin edit a user from the row menu', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create([
        'name' => 'Editable Person',
        'email' => 'editable@example.com',
    ]);

    $this->actingAs($admin);

    $page = visit('/users');

    $page->assertSee('editable@example.com')
        ->assertNoJavaScriptErrors()
        ->click("[data-user-actions=\"{$user->id}\"]")
        ->click("[data-edit-user=\"{$user->id}\"]")
        ->fill('#edit-user-name', 'Edited Person')
        ->fill('#edit-user-email', 'edited@example.com')
        ->click('[data-testid="edit-role-select-trigger"]')
        ->click('[data-testid="edit-role-option-admin"]')
        ->click('Save changes')
        ->waitForText('edited@example.com')
        ->assertSee('edited@example.com')
        ->assertDontSee('editable@example.com');

    $user->refresh();

    expect($user->name)->toBe('Edited Person')
        ->and($user->email)->toBe('edited@example.com')
        ->and($user->role->name)->toBe('admin');
});

it('lets an admin delete a user from the row menu', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create([
        'name' => 'Deleted Person',
        'email' => 'deleted@example.com',
    ]);

    $this->actingAs($admin);

    $page = visit('/users');

    $page->assertSee('deleted@example.com')
        ->assertNoJavaScriptErrors()
        ->click("[data-user-actions=\"{$user->id}\"]")
        ->assertAttributeContains("[data-delete-user=\"{$user->id}\"]", 'class', 'data-[variant=destructive]:text-destructive')
        ->click("[data-delete-user=\"{$user->id}\"]")
        ->assertSee('Delete user?')
        ->click('Delete user')
        ->assertDontSee('deleted@example.com');

    expect(User::query()->whereKey($user->id)->exists())->toBeFalse();
});

it('shows the users navigation item to users with permission', function () {
    $this->actingAs(User::factory()->admin()->create());

    visit('/dashboard')
        ->assertSee('Users')
        ->assertDontSee('DAV sync')
        ->assertNoJavaScriptErrors();
});

it('hides the users navigation item from users without permission', function () {
    $this->actingAs(User::factory()->create());

    visit('/dashboard')
        ->assertDontSee('Users')
        ->assertDontSee('DAV sync')
        ->assertNoJavaScriptErrors();
});

it('does not show user management in settings navigation', function () {
    $this->actingAs(User::factory()->admin()->create());

    visit('/settings/profile')
        ->assertDontSeeIn('nav[aria-label="Settings"]', 'Users')
        ->assertNoJavaScriptErrors();
});
