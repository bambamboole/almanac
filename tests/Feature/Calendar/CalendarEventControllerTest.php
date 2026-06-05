<?php

use App\Models\User;
use Bambamboole\LaravelDav\Models\DavCalendarObject;

it('updates an event from the app', function () {
    $user = User::factory()->create();
    $calendar = davCalendarFor($user);
    $event = DavCalendarObject::factory()->for($calendar, 'calendar')->state(davData(['summary' => 'Old']))->create();

    $this->actingAs($user)
        ->from('/calendar')
        ->put("/calendar/events/{$event->id}", ['data' => ['summary' => 'New'], 'expected_etag' => $event->etag])
        ->assertRedirect('/calendar')
        ->assertInertiaFlash('toast.type', 'success')
        ->assertInertiaFlash('toast.message', 'Event updated.');

    expect($event->refresh()->data->summary)->toBe('New');
});

it('returns 409 on a stale etag', function () {
    $user = User::factory()->create();
    $calendar = davCalendarFor($user);
    $event = DavCalendarObject::factory()->for($calendar, 'calendar')->create();

    $this->actingAs($user)
        ->putJson("/calendar/events/{$event->id}", ['data' => ['summary' => 'Newer'], 'expected_etag' => 'stale'])
        ->assertStatus(409);
});

it('forbids editing another users event', function () {
    $event = DavCalendarObject::factory()->create();

    $this->actingAs(User::factory()->create())
        ->putJson("/calendar/events/{$event->id}", ['data' => ['summary' => 'x'], 'expected_etag' => $event->etag])
        ->assertForbidden();
});

it('deletes an event from the app', function () {
    $user = User::factory()->create();
    $calendar = davCalendarFor($user);
    $event = DavCalendarObject::factory()->for($calendar, 'calendar')->create();

    $this->actingAs($user)
        ->from('/calendar')
        ->delete("/calendar/events/{$event->id}", ['expected_etag' => $event->etag])
        ->assertRedirect('/calendar')
        ->assertInertiaFlash('toast.type', 'success')
        ->assertInertiaFlash('toast.message', 'Event deleted.');

    expect(DavCalendarObject::query()->whereKey($event->id)->exists())->toBeFalse();
});

it('forbids deleting another users event', function () {
    $event = DavCalendarObject::factory()->create();

    $this->actingAs(User::factory()->create())
        ->deleteJson("/calendar/events/{$event->id}", ['expected_etag' => $event->etag])
        ->assertForbidden();
});
