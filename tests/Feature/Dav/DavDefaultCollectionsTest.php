<?php

use App\Actions\Dav\CreateDavCredential;
use App\Models\User;
use Bambamboole\LaravelDav\Models\DavAddressBook;
use Bambamboole\LaravelDav\Models\DavCalendar;
use Bambamboole\LaravelDav\Models\DavCalendarInstance;

it('creates default dav collections when dav is enabled', function () {
    $user = User::factory()->create();

    app(CreateDavCredential::class)->handle($user, 'Phone');
    app(CreateDavCredential::class)->handle($user, 'Laptop');

    expect(DavCalendarInstance::query()->where('owner_id', $user->id)->where('uri', 'personal')->exists())->toBeTrue()
        ->and(DavAddressBook::query()->where('owner_id', $user->id)->where('uri', 'personal')->exists())->toBeTrue()
        ->and(DavCalendar::query()->where('owner_id', $user->id)->count())->toBe(1)
        ->and(DavCalendarInstance::query()->where('owner_id', $user->id)->where('uri', 'personal')->count())->toBe(1)
        ->and(DavAddressBook::query()->where('owner_id', $user->id)->where('uri', 'personal')->count())->toBe(1);
});
