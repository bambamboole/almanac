<?php

use App\Actions\Dav\CreateDavCredential;
use App\Actions\Dav\RevokeDavCredential;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('authenticates dav requests with a generated dav credential', function () {
    $user = User::factory()->create(['email' => 'ada@example.com']);
    $credential = app(CreateDavCredential::class)->handle($user, 'Phone');

    expect($credential['credential']->secret_hash)->not->toBe($credential['plainSecret'])
        ->and(Hash::check($credential['plainSecret'], $credential['credential']->secret_hash))->toBeTrue()
        ->and($credential['credential']->last_used_at)->toBeNull()
        ->and($credential['username'])->toMatch('/^dav-'.$user->getKey().'-[a-z0-9]{8}$/');

    $this->call('PROPFIND', '/dav/', server: davBasicAuth($credential['username'], $credential['plainSecret']))
        ->assertStatus(207)
        ->assertHeader('Content-Type', 'application/xml; charset=utf-8')
        ->assertSee('/dav/principals/', false);

    expect($credential['credential']->refresh()->last_used_at)->not->toBeNull();
});

it('rejects the normal account password for dav requests', function () {
    $user = User::factory()->create([
        'email' => 'ada@example.com',
        'password' => bcrypt('account-password'),
    ]);

    $this->call('PROPFIND', '/dav/', server: davBasicAuth($user->email, 'account-password'))
        ->assertUnauthorized();
});

it('rejects a wrong secret for a valid dav username', function () {
    $user = User::factory()->create();
    $credential = app(CreateDavCredential::class)->handle($user, 'Phone');

    $this->call('PROPFIND', '/dav/', server: davBasicAuth($credential['username'], 'wrong-secret'))
        ->assertUnauthorized();
});

it('revokes dav credentials', function () {
    $user = User::factory()->create();
    $credential = app(CreateDavCredential::class)->handle($user, 'Phone');

    app(RevokeDavCredential::class)->handle($credential['credential']);

    expect($credential['credential']->fresh())->toBeNull();

    $this->call('PROPFIND', '/dav/', server: davBasicAuth($credential['username'], $credential['plainSecret']))
        ->assertUnauthorized();
});

it('serves authenticated requests through the sabredav principal tree', function (string $method, string $path, int $status, ?string $contentType = null) {
    $user = User::factory()->create();
    $credential = app(CreateDavCredential::class)->handle($user, 'Phone');

    $response = $this->call($method, $path, server: davBasicAuth($credential['username'], $credential['plainSecret']))
        ->assertStatus($status);

    if ($contentType !== null) {
        $response->assertHeader('Content-Type', $contentType);
    }
})->with([
    'propfind root without trailing slash' => ['PROPFIND', '/dav', 207],
    'get root is not implemented for collections' => ['GET', '/dav/', 501],
    'propfind principals collection' => ['PROPFIND', '/dav/principals', 207],
    'get principals collection is not implemented for collections' => ['GET', '/dav/principals', 501],
]);
