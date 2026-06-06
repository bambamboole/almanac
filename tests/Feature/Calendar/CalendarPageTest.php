<?php

use App\Models\User;
use Bambamboole\LaravelDav\Models\DavCalendar;
use Bambamboole\LaravelDav\Models\DavCalendarInstance;
use Bambamboole\LaravelDav\Models\DavCalendarObject;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

test('guests cannot access the calendar page', function () {
    $this->get('/calendar')->assertRedirect('/login');
});

test('authenticated user gets the calendar page payload', function () {
    $user = User::factory()->create();
    $calendar = davCalendarFor($user, [
        'display_name' => 'Personal',
        'color' => '#2563eb',
    ]);

    DavCalendarObject::factory()->for($calendar, 'calendar')->state(davData([
        'summary' => 'Planning review',
        'location' => 'Office',
        'description' => 'Quarterly planning session',
    ]))->create([
        'starts_at' => now()->addDay()->setTime(9, 0),
        'ends_at' => now()->addDay()->setTime(10, 0),
        'is_all_day' => false,
        'calendar_data' => 'raw calendar data should not be exposed',
    ]);

    $this->actingAs($user)
        ->get('/calendar')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('calendar/index')
            ->has('window.start')
            ->has('window.end')
            ->has('calendars', 1, fn (Assert $page) => $page
                ->where('id', $calendar->id)
                ->where('display_name', 'Personal')
                ->where('color', '#2563eb')
                ->etc(),
            )
            ->has('events', 1, fn (Assert $page) => $page
                ->where('data.summary', 'Planning review')
                ->where('data.location', 'Office')
                ->where('data.description', 'Quarterly planning session')
                ->where('calendar.id', $calendar->id)
                ->where('calendar.display_name', 'Personal')
                ->where('calendar.color', '#2563eb')
                ->where('is_all_day', false)
                ->has('starts_at')
                ->has('ends_at')
                ->missing('calendar_data')
                ->etc(),
            ),
        );
});

test('calendar page exposes date-only fields for all-day events', function () {
    $user = User::factory()->create();
    $calendar = davCalendarFor($user);

    DavCalendarObject::factory()->for($calendar, 'calendar')->state(davData(['summary' => 'Conference']))->create([
        'starts_at' => '2026-06-03 00:00:00',
        'ends_at' => '2026-06-04 00:00:00',
        'is_all_day' => true,
    ]);

    $this->actingAs($user)
        ->get('/calendar')
        ->assertInertia(fn (Assert $page) => $page
            ->where('events.0.data.summary', 'Conference')
            ->where('events.0.is_all_day', true)
            ->where('events.0.starts_at', fn (string $startsAt) => str_starts_with($startsAt, '2026-06-03'))
            ->where('events.0.ends_at', fn (string $endsAt) => str_starts_with($endsAt, '2026-06-04')),
        );
});

test('calendar page excludes another users events', function () {
    $user = User::factory()->create();
    $calendar = davCalendarFor($user);
    $otherCalendar = DavCalendar::factory()->create();

    $ownedEvent = DavCalendarObject::factory()->for($calendar, 'calendar')->state(davData(['summary' => 'Owned event']))->create([
        'starts_at' => now()->addDay(),
        'ends_at' => now()->addDay()->addHour(),
    ]);

    DavCalendarObject::factory()->for($otherCalendar, 'calendar')->state(davData(['summary' => 'Other user event']))->create([
        'starts_at' => now()->addDay(),
        'ends_at' => now()->addDay()->addHour(),
    ]);

    $response = $this->actingAs($user)->get('/calendar');

    $events = $response->inertiaProps('events');

    expect($events)->toHaveCount(1)
        ->and($events[0]['id'])->toBe($ownedEvent->id)
        ->and($events[0]['data']['summary'])->toBe('Owned event');
});

test('calendar page includes shared calendar events with recipient instance metadata', function () {
    $owner = User::factory()->create();
    $recipient = User::factory()->create();
    $calendar = davCalendarFor($owner, [
        'display_name' => 'Owner calendar',
        'color' => '#111111',
    ]);
    $instance = $calendar->shareWith($recipient, DavCalendarInstance::AccessRead);
    $instance->updateDavProperties([
        'display_name' => 'Shared planning',
        'color' => '#abcdef',
    ]);

    $event = DavCalendarObject::factory()->for($calendar, 'calendar')->state(davData(['summary' => 'Shared review']))->create([
        'starts_at' => now()->addDay(),
        'ends_at' => now()->addDay()->addHour(),
    ]);

    $response = $this->actingAs($recipient)->get('/calendar');

    $calendars = $response->inertiaProps('calendars');
    $events = $response->inertiaProps('events');

    expect($calendars)->toHaveCount(1)
        ->and($calendars[0]['id'])->toBe($calendar->id)
        ->and($calendars[0]['display_name'])->toBe('Shared planning')
        ->and($calendars[0]['color'])->toBe('#abcdef')
        ->and($calendars[0]['access'])->toBe(DavCalendarInstance::AccessRead)
        ->and($calendars[0]['is_owner'])->toBeFalse()
        ->and($calendars[0]['is_shared'])->toBeTrue()
        ->and($calendars[0]['can_write'])->toBeFalse()
        ->and($events)->toHaveCount(1)
        ->and($events[0]['id'])->toBe($event->id)
        ->and($events[0]['data']['summary'])->toBe('Shared review')
        ->and($events[0]['calendar']['display_name'])->toBe('Shared planning')
        ->and($events[0]['calendar']['color'])->toBe('#abcdef')
        ->and($events[0]['calendar']['access'])->toBe(DavCalendarInstance::AccessRead)
        ->and($events[0]['calendar']['can_write'])->toBeFalse();
});

test('calendar page excludes events outside the planned window', function () {
    $user = User::factory()->create();
    $calendar = davCalendarFor($user);
    $insideWindow = DavCalendarObject::factory()->for($calendar, 'calendar')->state(davData(['summary' => 'Inside window']))->create([
        'starts_at' => now()->addDay(),
        'ends_at' => now()->addDay()->addHour(),
    ]);

    DavCalendarObject::factory()->for($calendar, 'calendar')->state(davData(['summary' => 'Past event']))->create([
        'starts_at' => now()->subMonths(2),
        'ends_at' => now()->subMonths(2)->addHour(),
    ]);

    DavCalendarObject::factory()->for($calendar, 'calendar')->state(davData(['summary' => 'Future event']))->create([
        'starts_at' => now()->addMonths(4),
        'ends_at' => now()->addMonths(4)->addHour(),
    ]);

    $events = $this->actingAs($user)
        ->get('/calendar')
        ->inertiaProps('events');

    expect($events)->toHaveCount(1)
        ->and($events[0]['id'])->toBe($insideWindow->id)
        ->and($events[0]['data']['summary'])->toBe('Inside window');
});

test('calendar event props do not expose raw calendar data', function () {
    $user = User::factory()->create();
    $calendar = davCalendarFor($user);

    DavCalendarObject::factory()->for($calendar, 'calendar')->create([
        'starts_at' => now()->addDay(),
        'ends_at' => now()->addDay()->addHour(),
        'calendar_data' => 'BEGIN:VCALENDAR',
    ]);

    $event = $this->actingAs($user)
        ->get('/calendar')
        ->inertiaProps('events.0');

    expect($event)->not->toHaveKey('calendar_data');
});
