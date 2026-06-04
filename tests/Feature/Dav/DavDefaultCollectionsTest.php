<?php

use App\Actions\Dav\CreateDavCredential;
use App\Models\User;
use Bambamboole\LaravelDav\Models\DavAddressBook;
use Bambamboole\LaravelDav\Models\DavCalendar;

it('creates default dav collections when dav is enabled', function () {
    $user = User::factory()->create();

    app(CreateDavCredential::class)->handle($user, 'Phone');
    app(CreateDavCredential::class)->handle($user, 'Laptop');

    expect(DavCalendar::query()->whereBelongsTo($user)->where('uri', 'personal')->exists())->toBeTrue()
        ->and(DavAddressBook::query()->whereBelongsTo($user)->where('uri', 'personal')->exists())->toBeTrue()
        ->and(DavCalendar::query()->whereBelongsTo($user)->where('uri', 'personal')->count())->toBe(1)
        ->and(DavAddressBook::query()->whereBelongsTo($user)->where('uri', 'personal')->count())->toBe(1);
});
