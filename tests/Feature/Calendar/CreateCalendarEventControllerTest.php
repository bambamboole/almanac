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
            'summary' => 'Lunch',
            'starts_at' => '2026-06-11T12:30:00Z',
            'ends_at' => '2026-06-11T13:30:00Z',
            'is_all_day' => false,
        ])
        ->assertRedirect()
        ->assertInertiaFlash('toast.type', 'success')
        ->assertInertiaFlash('toast.message', 'Event created.');

    expect(DavCalendarObject::query()->where('dav_calendar_id', $calendar->id)->where('summary', 'Lunch')->exists())->toBeTrue();
});

it('forbids creating an event on another user\'s calendar', function () {
    $user = User::factory()->create();
    $calendar = DavCalendar::factory()->for(User::factory())->create();

    $this->actingAs($user)
        ->post('/calendar/events', [
            'calendar_id' => $calendar->id,
            'summary' => 'Sneaky',
            'starts_at' => '2026-06-11T12:30:00Z',
            'ends_at' => '2026-06-11T13:30:00Z',
        ])
        ->assertSessionHasErrors('calendar_id');
});
