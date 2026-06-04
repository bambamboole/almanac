<?php

use App\Actions\Install\SeedDemoData;
use App\Models\User;
use Bambamboole\LaravelDav\Models\DavAddressBook;
use Bambamboole\LaravelDav\Models\DavCalendar;

it('attaches the demo calendar and contacts to a user', function () {
    $user = User::factory()->admin()->create();

    (new SeedDemoData)->handle($user);

    $calendar = DavCalendar::query()
        ->whereBelongsTo($user)
        ->where('uri', 'personal')
        ->firstOrFail();

    $addressBook = DavAddressBook::query()
        ->whereBelongsTo($user)
        ->where('uri', 'personal')
        ->firstOrFail();

    expect($calendar->display_name)->toBe('Personal')
        ->and($calendar->objects()->count())->toBe(4)
        ->and($calendar->objects()->where('is_all_day', true)->count())->toBe(1)
        ->and($addressBook->display_name)->toBe('Personal')
        ->and($addressBook->cards()->count())->toBe(5);
});
