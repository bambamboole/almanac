<?php

use App\Models\User;
use Bambamboole\LaravelDav\Parsing\CalendarObjectParser;
use Illuminate\Support\Carbon;

it('creates a named calendar with a given number of events', function () {
    $user = User::factory()->withCalendar('Work', 3)->create();

    $calendar = $user->davCalendars()->sole();
    $calendarInstance = $calendar->ownerInstance;

    expect($user->davCalendars)->toHaveCount(1)
        ->and($calendarInstance?->display_name)->toBe('Work')
        ->and($calendarInstance?->uri)->toBe('work')
        ->and($calendar->objects)->toHaveCount(3);
});

it('creates calendar events from attribute-override maps', function () {
    $user = User::factory()->withCalendar('Personal', [
        ['summary' => 'Standup'],
        ['summary' => 'Retro'],
    ])->create();

    $summaries = $user->davCalendars()->sole()->objects->map(fn ($object) => $object->data->summary);

    expect($summaries)->toHaveCount(2)
        ->and($summaries->all())->toContain('Standup', 'Retro');
});

it('keeps generated events within the given period', function () {
    $start = Carbon::parse('2026-09-01 00:00:00', 'UTC');
    $end = Carbon::parse('2026-09-07 00:00:00', 'UTC');

    $user = User::factory()->withCalendar('Trip', 5, [$start, $end])->create();

    $user->davCalendars()->sole()->objects->each(function ($object) use ($start, $end): void {
        expect($object->starts_at->betweenIncluded($start, $end))->toBeTrue();
    });
});

it('produces parseable payloads for generated events', function () {
    $user = User::factory()->withCalendar('Personal', 1)->create();

    $object = $user->davCalendars()->sole()->objects()->sole();
    $parsed = app(CalendarObjectParser::class)->parse($object->calendar_data);

    expect($parsed->summary)->toBe($object->data->summary)
        ->and($object->etag)->toBe(sha1($object->calendar_data));
});

it('supports multiple calendars on one user', function () {
    $user = User::factory()
        ->withCalendar('Work', 1)
        ->withCalendar('Personal', 2)
        ->create();

    expect($user->davCalendars)->toHaveCount(2)
        ->and($user->davCalendarInstances->pluck('display_name')->all())->toContain('Work', 'Personal');
});

it('creates a personal address book with a given number of contacts', function () {
    $user = User::factory()->withContacts(4)->create();

    $addressBook = $user->davAddressBooks()->sole();

    expect($user->davAddressBooks)->toHaveCount(1)
        ->and($addressBook->display_name)->toBe('Personal')
        ->and($addressBook->cards)->toHaveCount(4);
});

it('creates contacts from attribute-override maps', function () {
    $user = User::factory()->withContacts([
        ['formattedName' => 'Ada Lovelace', 'emailAddresses' => [['value' => 'ada@example.com']]],
    ])->create();

    $card = $user->davAddressBooks()->sole()->cards()->sole();

    expect($card->data->formattedName)->toBe('Ada Lovelace')
        ->and(collect($card->data->emailAddresses)->pluck('value')->all())->toBe(['ada@example.com'])
        ->and($card->etag)->toBe(sha1($card->card_data));
});
