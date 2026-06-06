<?php

use App\Models\User;
use Bambamboole\LaravelDav\Models\DavCalendarInstance;
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

it('updates an event from a shared read-write calendar', function () {
    $owner = User::factory()->create();
    $recipient = User::factory()->create();
    $calendar = davCalendarFor($owner);
    $calendar->shareWith($recipient, DavCalendarInstance::AccessReadWrite);
    $event = DavCalendarObject::factory()->for($calendar, 'calendar')->state(davData(['summary' => 'Old shared']))->create();

    $this->actingAs($recipient)
        ->from('/calendar')
        ->put("/calendar/events/{$event->id}", ['data' => ['summary' => 'New shared'], 'expected_etag' => $event->etag])
        ->assertRedirect('/calendar')
        ->assertInertiaFlash('toast.type', 'success')
        ->assertInertiaFlash('toast.message', 'Event updated.');

    expect($event->refresh()->data->summary)->toBe('New shared');
});

it('forbids updating an event from a shared read-only calendar', function () {
    $owner = User::factory()->create();
    $recipient = User::factory()->create();
    $calendar = davCalendarFor($owner);
    $calendar->shareWith($recipient, DavCalendarInstance::AccessRead);
    $event = DavCalendarObject::factory()->for($calendar, 'calendar')->state(davData(['summary' => 'Readonly shared']))->create();

    $this->actingAs($recipient)
        ->putJson("/calendar/events/{$event->id}", ['data' => ['summary' => 'Blocked'], 'expected_etag' => $event->etag])
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

it('deletes an event from a shared read-write calendar', function () {
    $owner = User::factory()->create();
    $recipient = User::factory()->create();
    $calendar = davCalendarFor($owner);
    $calendar->shareWith($recipient, DavCalendarInstance::AccessReadWrite);
    $event = DavCalendarObject::factory()->for($calendar, 'calendar')->create();

    $this->actingAs($recipient)
        ->from('/calendar')
        ->delete("/calendar/events/{$event->id}", ['expected_etag' => $event->etag])
        ->assertRedirect('/calendar')
        ->assertInertiaFlash('toast.type', 'success')
        ->assertInertiaFlash('toast.message', 'Event deleted.');

    expect(DavCalendarObject::query()->whereKey($event->id)->exists())->toBeFalse();
});

it('forbids deleting an event from a shared read-only calendar', function () {
    $owner = User::factory()->create();
    $recipient = User::factory()->create();
    $calendar = davCalendarFor($owner);
    $calendar->shareWith($recipient, DavCalendarInstance::AccessRead);
    $event = DavCalendarObject::factory()->for($calendar, 'calendar')->create();

    $this->actingAs($recipient)
        ->deleteJson("/calendar/events/{$event->id}", ['expected_etag' => $event->etag])
        ->assertForbidden();
});
