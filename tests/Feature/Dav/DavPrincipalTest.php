<?php

use App\Actions\Dav\CreateDavCredential;
use App\Models\User;

it('exposes the authenticated user principal', function () {
    $user = User::factory()->create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com']);
    $credential = app(CreateDavCredential::class)->handle($user, 'Mac');

    $body = <<<'XML'
<?xml version="1.0" encoding="utf-8" ?>
<d:propfind xmlns:d="DAV:">
  <d:prop>
    <d:displayname />
    <d:principal-URL />
  </d:prop>
</d:propfind>
XML;

    $this->withBasicAuth($credential['username'], $credential['plainSecret'])
        ->call('PROPFIND', '/dav/principals/'.$user->id, [], [], [], [
            'CONTENT_TYPE' => 'application/xml',
            'HTTP_DEPTH' => '0',
            'HTTP_AUTHORIZATION' => 'Basic '.base64_encode($credential['username'].':'.$credential['plainSecret']),
        ], $body)
        ->assertSuccessful()
        ->assertSee('Ada Lovelace', false)
        ->assertSee('/dav/principals/'.$user->id, false);
});
