<?php

it('redirects well known caldav and carddav endpoints to the dav root', function (string $path) {
    $this->get($path)
        ->assertRedirect('/dav/');
})->with([
    'caldav' => '/.well-known/caldav',
    'carddav' => '/.well-known/carddav',
]);

it('redirects well known endpoints for webdav discovery methods', function (string $method, string $path) {
    $this->call($method, $path)
        ->assertRedirect('/dav/');
})->with([
    'PROPFIND caldav' => ['PROPFIND', '/.well-known/caldav'],
    'PROPFIND carddav' => ['PROPFIND', '/.well-known/carddav'],
    'OPTIONS caldav' => ['OPTIONS', '/.well-known/caldav'],
    'OPTIONS carddav' => ['OPTIONS', '/.well-known/carddav'],
]);

it('returns unauthorized for unauthenticated dav requests', function (string $path) {
    $this->call('PROPFIND', $path)
        ->assertUnauthorized()
        ->assertHeader('WWW-Authenticate', 'Basic realm="'.config('dav.realm').'", charset="UTF-8"');
})->with([
    'dav root' => '/dav',
    'dav root with trailing slash' => '/dav/',
    'nested dav path' => '/dav/principals',
]);
