<?php

use App\Actions\Dav\CreateDavCredential;
use App\Models\User;

it('reports calendar object changes through webdav sync', function () {
    $user = User::factory()->create();
    $credential = app(CreateDavCredential::class)->handle($user, 'Phone');
    $authHeader = calDavAuthHeader($credential['username'], $credential['plainSecret']);
    $payload = calDavPayload('VEVENT', [
        'UID' => 'event-1',
        'DTSTAMP' => '20260603T000000Z',
        'SUMMARY' => 'Deep Work',
        'DTSTART' => '20260603T070000Z',
        'DTEND' => '20260603T083000Z',
    ]);

    calDavPut(
        $this,
        '/dav/calendars/'.$user->id.'/personal/event-1.ics',
        $authHeader,
        $payload,
    )->assertSuccessful();

    davSyncReport(
        $this,
        '/dav/calendars/'.$user->id.'/personal/',
        $authHeader,
        'http://sabredav.org/ns/sync/1',
    )
        ->assertSuccessful()
        ->assertSee('event-1.ics', false)
        ->assertSee('sync-token', false)
        ->assertSee('http://sabredav.org/ns/sync/2', false);
});

it('reports address book card changes through webdav sync', function () {
    $user = User::factory()->create();
    $credential = app(CreateDavCredential::class)->handle($user, 'Phone');
    $authHeader = cardDavAuthHeader($credential['username'], $credential['plainSecret']);
    $payload = cardDavPayload([
        'UID' => 'contact-1',
        'FN' => 'Ada Lovelace',
        'N' => ['value' => ['Lovelace', 'Ada', '', '', '']],
        'EMAIL' => ['value' => 'ada@example.com', 'parameters' => ['TYPE' => 'work']],
    ]);

    cardDavPut(
        $this,
        '/dav/addressbooks/'.$user->id.'/personal/contact-1.vcf',
        $authHeader,
        $payload,
    )->assertSuccessful();

    davSyncReport(
        $this,
        '/dav/addressbooks/'.$user->id.'/personal/',
        $authHeader,
        'http://sabredav.org/ns/sync/1',
    )
        ->assertSuccessful()
        ->assertSee('contact-1.vcf', false)
        ->assertSee('sync-token', false)
        ->assertSee('http://sabredav.org/ns/sync/2', false);
});

it('returns prefixed sync tokens through propfind', function () {
    $user = User::factory()->create();
    $credential = app(CreateDavCredential::class)->handle($user, 'Phone');
    $authHeader = calDavAuthHeader($credential['username'], $credential['plainSecret']);

    $response = $this->call('PROPFIND', '/dav/calendars/'.$user->id.'/personal/', [], [], [], [
        'CONTENT_TYPE' => 'application/xml',
        'HTTP_AUTHORIZATION' => $authHeader,
    ], <<<'XML'
<?xml version="1.0" encoding="utf-8" ?>
<d:propfind xmlns:d="DAV:">
    <d:prop>
        <d:sync-token />
    </d:prop>
</d:propfind>
XML);

    $response
        ->assertSuccessful()
        ->assertSee('sync-token', false)
        ->assertSee('http://sabredav.org/ns/sync/1', false);
});

it('validates sync tokens in conditional dav requests', function () {
    $user = User::factory()->create();
    $credential = app(CreateDavCredential::class)->handle($user, 'Phone');
    $path = '/dav/calendars/'.$user->id.'/personal/';
    $authHeader = calDavAuthHeader($credential['username'], $credential['plainSecret']);

    $this->call('PROPFIND', $path, [], [], [], [
        'CONTENT_TYPE' => 'application/xml',
        'HTTP_AUTHORIZATION' => $authHeader,
        'HTTP_IF' => '(<http://sabredav.org/ns/sync/1>)',
    ], davCurrentUserPrincipalPropfind())
        ->assertSuccessful();

    $this->call('PROPFIND', $path, [], [], [], [
        'CONTENT_TYPE' => 'application/xml',
        'HTTP_AUTHORIZATION' => $authHeader,
        'HTTP_IF' => '(<http://sabredav.org/ns/sync/99>)',
    ], davCurrentUserPrincipalPropfind())
        ->assertStatus(412);
});
