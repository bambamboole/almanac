<?php

use App\Models\User;
use Bambamboole\LaravelDav\Models\DavCalendar;
use Bambamboole\LaravelDav\Models\DavCalendarObject;

it('creates an event for a calendar the user owns', function () {
    $user = User::factory()->create();
    $calendar = DavCalendar::factory()->for($user)->create();

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
    $calendar = DavCalendar::factory()->for(User::factory())->create();

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
