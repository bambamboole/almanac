<?php

use App\Models\User;
use Bambamboole\LaravelDav\Models\DavCalendarInstance;
use Bambamboole\LaravelDav\Models\DavCalendarObject;

it('creates an event for a calendar the user owns', function () {
    $user = User::factory()->create();
    $calendar = davCalendarFor($user);

    $this->actingAs($user)
        ->post('/calendar/events', [
            'calendar_id' => $calendar->id,
            'data' => [
                'summary' => 'Lunch',
                'startsAt' => '2026-06-11T12:30:00Z',
                'endsAt' => '2026-06-11T13:30:00Z',
                'isAllDay' => false,
            ],
        ])
        ->assertRedirect()
        ->assertInertiaFlash('toast.type', 'success')
        ->assertInertiaFlash('toast.message', 'Event created.');

    $event = DavCalendarObject::query()->where('dav_calendar_id', $calendar->id)->firstOrFail();
    expect($event->data->summary)->toBe('Lunch');
});

it('forbids creating an event on another user\'s calendar', function () {
    $user = User::factory()->create();
    $calendar = davCalendarFor(User::factory());

    $this->actingAs($user)
        ->post('/calendar/events', [
            'calendar_id' => $calendar->id,
            'data' => [
                'summary' => 'Sneaky',
                'startsAt' => '2026-06-11T12:30:00Z',
                'endsAt' => '2026-06-11T13:30:00Z',
            ],
        ])
        ->assertSessionHasErrors('calendar_id');
});

it('creates an event for a shared read-write calendar', function () {
    $owner = User::factory()->create();
    $recipient = User::factory()->create();
    $calendar = davCalendarFor($owner);

    $calendar->shareWith($recipient, DavCalendarInstance::AccessReadWrite);

    $this->actingAs($recipient)
        ->post('/calendar/events', [
            'calendar_id' => $calendar->id,
            'data' => [
                'summary' => 'Shared lunch',
                'startsAt' => '2026-06-11T12:30:00Z',
                'endsAt' => '2026-06-11T13:30:00Z',
                'isAllDay' => false,
            ],
        ])
        ->assertRedirect()
        ->assertInertiaFlash('toast.type', 'success')
        ->assertInertiaFlash('toast.message', 'Event created.');

    $event = DavCalendarObject::query()->where('dav_calendar_id', $calendar->id)->firstOrFail();
    expect($event->data->summary)->toBe('Shared lunch');
});

it('forbids creating an event for a shared read-only calendar', function () {
    $owner = User::factory()->create();
    $recipient = User::factory()->create();
    $calendar = davCalendarFor($owner);

    $calendar->shareWith($recipient, DavCalendarInstance::AccessRead);

    $this->actingAs($recipient)
        ->post('/calendar/events', [
            'calendar_id' => $calendar->id,
            'data' => [
                'summary' => 'Read only lunch',
                'startsAt' => '2026-06-11T12:30:00Z',
                'endsAt' => '2026-06-11T13:30:00Z',
            ],
        ])
        ->assertSessionHasErrors('calendar_id');
});
