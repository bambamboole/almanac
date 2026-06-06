<?php

use App\Models\User;
use Bambamboole\LaravelDav\Models\DavCredential;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

test('guests cannot manage DAV credentials', function () {
    $credential = DavCredential::factory()->create();

    $this->get('/settings/dav')->assertRedirect('/login');
    $this->post('/settings/dav/credentials', ['name' => 'Phone'])->assertRedirect('/login');
    $this->delete("/settings/dav/credentials/{$credential->id}")->assertRedirect('/login');
});

test('DAV credential settings page is displayed', function () {
    $user = User::factory()->create();
    $newerCredential = DavCredential::factory()
        ->for($user, 'owner')
        ->create([
            'name' => 'Phone',
            'created_at' => now(),
            'last_used_at' => now()->subMinute(),
        ]);
    $olderCredential = DavCredential::factory()
        ->for($user, 'owner')
        ->create([
            'name' => 'Laptop',
            'created_at' => now()->subDay(),
            'last_used_at' => null,
        ]);
    DavCredential::factory()->create();

    $this->actingAs($user)
        ->get('/settings/dav')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/dav')
            ->has('davUrl')
            ->has('credentials', 2)
            ->where('credentials.0.id', $newerCredential->id)
            ->where('credentials.0.name', 'Phone')
            ->where('credentials.0.username', $newerCredential->username)
            ->has('credentials.0.created_at_diff')
            ->has('credentials.0.last_used_at_diff')
            ->missing('credentials.0.secret_hash')
            ->where('credentials.1.id', $olderCredential->id)
            ->missing('credentials.1.secret_hash'),
        );
});

test('DAV credential can be created', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->post('/settings/dav/credentials', [
            'name' => 'Phone',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/settings/dav')
        ->assertSessionHas('inertia.flash_data.createdDavCredential', fn (array $credential): bool => filled($credential['username'] ?? null)
            && filled($credential['plainSecret'] ?? null));

    $credential = DavCredential::query()->whereBelongsTo($user, 'owner')->sole();

    expect($credential->name)->toBe('Phone')
        ->and($credential->secret_hash)->not->toBe($response->getSession()->get('inertia.flash_data')['createdDavCredential']['plainSecret'])
        ->and(DavCredential::query()->whereBelongsTo($otherUser, 'owner')->exists())->toBeFalse();

    $this->actingAs($user)
        ->get('/settings/dav')
        ->assertInertia(fn (Assert $page) => $page
            ->has('credentials', 1, fn (Assert $page) => $page
                ->where('id', $credential->id)
                ->missing('secret_hash')
                ->etc(),
            ),
        );
});

test('owned DAV credential can be revoked', function () {
    $user = User::factory()->create();
    $credential = DavCredential::factory()->for($user, 'owner')->create();

    $this->actingAs($user)
        ->delete("/settings/dav/credentials/{$credential->id}")
        ->assertRedirect('/settings/dav');

    expect($credential->fresh())->toBeNull();
});

test('another users DAV credential cannot be revoked', function () {
    $user = User::factory()->create();
    $credential = DavCredential::factory()->create();

    $this->actingAs($user)
        ->delete("/settings/dav/credentials/{$credential->id}")
        ->assertNotFound();

    expect($credential->fresh())->not->toBeNull();
});

test('DAV credential name is required', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from('/settings/dav')
        ->post('/settings/dav/credentials', ['name' => ''])
        ->assertSessionHasErrors('name')
        ->assertRedirect('/settings/dav');
});

test('DAV credential name cannot be longer than 80 characters', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from('/settings/dav')
        ->post('/settings/dav/credentials', ['name' => str_repeat('a', 81)])
        ->assertSessionHasErrors('name')
        ->assertRedirect('/settings/dav');
});
