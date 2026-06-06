<?php

use App\Models\User;
use Bambamboole\LaravelDav\Models\DavCalendarInstance;
use Bambamboole\LaravelDav\Models\DavCalendarObject;

it('creates an event from the calendar UI', function () {
    $user = User::factory()->create();
    davCalendarFor($user, ['display_name' => 'Personal']);

    $this->actingAs($user);
    $page = visit('/calendar');

    $page->assertNoJavaScriptErrors()
        ->click('New event')
        ->fill('summary', 'Dentist')
        ->fill('starts_at', '2026-06-12T10:00')
        ->fill('ends_at', '2026-06-12T11:00')
        ->click('Create event')
        ->assertSee('Dentist');

    expect(DavCalendarObject::query()->get()->contains(fn ($object): bool => $object->data->summary === 'Dentist'))->toBeTrue();
});

it('preserves the dragged time range when creating an event', function () {
    $user = User::factory()->create();
    $calendar = davCalendarFor($user, ['display_name' => 'Personal']);
    DavCalendarObject::factory()->for($calendar, 'calendar')->state(davData([
        'summary' => 'Existing appointment',
        'startsAt' => today()->setTime(8, 0),
        'endsAt' => today()->setTime(9, 0),
        'isAllDay' => false,
    ]))->create();

    $this->actingAs($user);
    $page = visit('/calendar');

    $page->assertNoJavaScriptErrors()
        ->assertSee('Existing appointment')
        ->click('Week')
        ->drag(
            '.fc-timegrid-slot-lane[data-time="09:00:00"]',
            '.fc-timegrid-slot-lane[data-time="14:00:00"]',
        )
        ->assertSee('New event')
        ->assertScript(
            "(() => {
                const start = document.querySelector('#starts_at').value;
                const end = document.querySelector('#ends_at').value;

                return (new Date(end) - new Date(start)) / 36e5 > 1;
            })()",
            true,
        );
});

it('creates a calendar from the calendar UI', function () {
    $user = User::factory()->create();

    $this->actingAs($user);
    $page = visit('/calendar');

    $page->assertNoJavaScriptErrors()
        ->click('[data-new-calendar]')
        ->fill('display_name', 'Side Projects')
        ->click('Create calendar')
        ->assertSee('Side Projects');

    expect(DavCalendarInstance::query()->where('owner_id', $user->id)->where('display_name', 'Side Projects')->exists())->toBeTrue();
});
