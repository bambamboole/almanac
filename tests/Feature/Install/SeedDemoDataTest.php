<?php

use App\Actions\Install\SeedDemoData;
use App\Models\User;
use Bambamboole\LaravelDav\Models\DavAddressBook;
use Bambamboole\LaravelDav\Models\DavCalendarInstance;

it('attaches the demo calendar and contacts to a user', function () {
    $user = User::factory()->admin()->create();

    (new SeedDemoData)->handle($user);

    $calendarInstance = DavCalendarInstance::query()
        ->where('owner_id', $user->id)
        ->where('uri', 'personal')
        ->firstOrFail();

    $addressBook = DavAddressBook::query()
        ->where('owner_id', $user->id)
        ->where('uri', 'personal')
        ->firstOrFail();

    expect($calendarInstance->display_name)->toBe('Personal')
        ->and($calendarInstance->calendar->objects()->count())->toBe(4)
        ->and($calendarInstance->calendar->objects()->where('is_all_day', true)->count())->toBe(1)
        ->and($addressBook->display_name)->toBe('Personal')
        ->and($addressBook->cards()->count())->toBe(5);
});
