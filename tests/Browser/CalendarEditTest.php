<?php

use App\Models\User;
use Bambamboole\LaravelDav\Models\DavCalendarObject;

it('edits a calendar event from the calendar page', function () {
    $user = User::factory()
        ->withCalendar('Personal', [
            [
                'summary' => 'Original summary',
                'startsAt' => today()->setTime(10, 0),
                'endsAt' => today()->setTime(11, 0),
                'isAllDay' => false,
            ],
        ])
        ->create();

    $this->actingAs($user);

    $event = DavCalendarObject::query()
        ->whereHas('calendar', fn ($q) => $q->where('owner_id', $user->id))
        ->firstOrFail();

    $page = visit('/calendar');

    $page->assertSee('Original summary')
        ->click("[data-edit-event=\"{$event->id}\"]")
        ->assertVisible('#edit-event-summary')
        ->clear('#edit-event-summary')
        ->fill('#edit-event-summary', 'Updated summary')
        ->click('button[type="submit"]')
        ->assertSee('Updated summary')
        ->assertNoJavaScriptErrors();
});

it('edits an event start time from the calendar page', function () {
    $user = User::factory()
        ->withCalendar('Personal', [
            [
                'summary' => 'Timed event',
                'startsAt' => today()->setTime(10, 0),
                'endsAt' => today()->setTime(11, 0),
                'isAllDay' => false,
            ],
        ])
        ->create();

    $this->actingAs($user);

    $event = DavCalendarObject::query()
        ->whereHas('calendar', fn ($q) => $q->where('owner_id', $user->id))
        ->firstOrFail();

    $page = visit('/calendar');

    $page->assertSee('Timed event')
        ->click("[data-edit-event=\"{$event->id}\"]")
        ->assertVisible('#edit-event-starts-at')
        ->fill('#edit-event-starts-at', '2026-08-15T14:30')
        ->fill('#edit-event-ends-at', '2026-08-15T15:30')
        ->click('button[type="submit"]')
        ->assertNoJavaScriptErrors();

    $event->refresh();
    expect($event->starts_at->toIso8601String())->toContain('2026-08-15T14:30');
});
