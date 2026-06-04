<?php

use App\Models\User;
use Bambamboole\LaravelDav\Models\DavAddressBook;
use Bambamboole\LaravelDav\Models\DavCalendar;
use Bambamboole\LaravelDav\Models\DavCalendarObject;
use Bambamboole\LaravelDav\Models\DavCard;

test('database seeder creates rerunnable demo life data', function () {
    $this->seed();
    $this->seed();

    $user = User::query()->where('email', 'demo@example.com')->firstOrFail();
    $calendar = DavCalendar::query()
        ->whereBelongsTo($user)
        ->where('uri', 'personal')
        ->firstOrFail();
    $addressBook = DavAddressBook::query()
        ->whereBelongsTo($user)
        ->where('uri', 'personal')
        ->firstOrFail();

    expect(User::query()->where('email', 'demo@example.com')->count())->toBe(1)
        ->and($calendar->display_name)->toBe('Personal')
        ->and($calendar->objects()->count())->toBe(4)
        ->and($calendar->objects()->where('is_all_day', true)->count())->toBe(1)
        ->and($addressBook->display_name)->toBe('Personal')
        ->and($addressBook->cards()->count())->toBe(5)
        ->and(DavCalendarObject::query()->where('summary', 'Weekly planning')->count())->toBe(1)
        ->and(DavCard::query()->where('full_name', 'Ada Lovelace')->count())->toBe(1);

    $ada = DavCard::query()->where('uid', 'demo-ada-lovelace')->firstOrFail();

    expect($ada->phone_numbers)->not->toBeEmpty()
        ->and($ada->email_addresses)->not->toBeEmpty()
        ->and($ada->addresses)->not->toBeEmpty()
        ->and($ada->urls)->not->toBeEmpty()
        ->and($ada->instant_messages)->not->toBeEmpty()
        ->and($ada->birthday)->not->toBeNull()
        ->and($ada->note)->not->toBeNull()
        ->and($ada->card_data)->toContain('ADR')
        ->and($ada->card_data)->toContain('URL')
        ->and($ada->card_data)->toContain('IMPP')
        ->and($ada->card_data)->toContain('BDAY');
});

test('database seeder creates a working fixed dav credential', function () {
    $this->seed();

    $this->call('PROPFIND', '/dav/', server: davBasicAuth('demo', 'password'))
        ->assertStatus(207);
});
