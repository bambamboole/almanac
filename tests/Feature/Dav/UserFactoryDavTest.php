<?php

use App\Models\User;
use Bambamboole\LaravelDav\Parsing\CalendarObjectParser;
use Illuminate\Support\Carbon;

it('creates a named calendar with a given number of events', function () {
    $user = User::factory()->withCalendar('Work', 3)->create();

    $calendar = $user->calendars()->sole();

    expect($user->calendars)->toHaveCount(1)
        ->and($calendar->display_name)->toBe('Work')
        ->and($calendar->uri)->toBe('work')
        ->and($calendar->objects)->toHaveCount(3);
});

it('creates calendar events from attribute-override maps', function () {
    $user = User::factory()->withCalendar('Personal', [
        ['summary' => 'Standup'],
        ['summary' => 'Retro'],
    ])->create();

    $summaries = $user->calendars()->sole()->objects->pluck('summary');

    expect($summaries)->toHaveCount(2)
        ->and($summaries->all())->toContain('Standup', 'Retro');
});

it('keeps generated events within the given period', function () {
    $start = Carbon::parse('2026-09-01 00:00:00', 'UTC');
    $end = Carbon::parse('2026-09-07 00:00:00', 'UTC');

    $user = User::factory()->withCalendar('Trip', 5, [$start, $end])->create();

    $user->calendars()->sole()->objects->each(function ($object) use ($start, $end): void {
        expect($object->starts_at->betweenIncluded($start, $end))->toBeTrue();
    });
});

it('produces parseable payloads for generated events', function () {
    $user = User::factory()->withCalendar('Personal', 1)->create();

    $object = $user->calendars()->sole()->objects()->sole();
    $parsed = app(CalendarObjectParser::class)->parse($object->calendar_data);

    expect($parsed->summary)->toBe($object->summary)
        ->and($object->etag)->toBe(sha1($object->calendar_data));
});

it('supports multiple calendars on one user', function () {
    $user = User::factory()
        ->withCalendar('Work', 1)
        ->withCalendar('Personal', 2)
        ->create();

    expect($user->calendars)->toHaveCount(2)
        ->and($user->calendars->pluck('display_name')->all())->toContain('Work', 'Personal');
});

it('creates a personal address book with a given number of contacts', function () {
    $user = User::factory()->withContacts(4)->create();

    $addressBook = $user->addressBooks()->sole();

    expect($user->addressBooks)->toHaveCount(1)
        ->and($addressBook->display_name)->toBe('Personal')
        ->and($addressBook->cards)->toHaveCount(4);
});

it('creates contacts from attribute-override maps', function () {
    $user = User::factory()->withContacts([
        ['full_name' => 'Ada Lovelace', 'emails' => ['ada@example.com']],
    ])->create();

    $card = $user->addressBooks()->sole()->cards()->sole();

    expect($card->full_name)->toBe('Ada Lovelace')
        ->and($card->emails)->toBe(['ada@example.com'])
        ->and($card->etag)->toBe(sha1($card->card_data));
});
