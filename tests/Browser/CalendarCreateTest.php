<?php

use App\Models\User;
use Bambamboole\LaravelDav\Models\DavCalendar;
use Bambamboole\LaravelDav\Models\DavCalendarObject;

it('creates an event from the calendar UI', function () {
    $user = User::factory()->create();
    DavCalendar::factory()->for($user)->create(['display_name' => 'Personal']);

    $this->actingAs($user);
    $page = visit('/calendar');

    $page->assertNoJavaScriptErrors()
        ->click('New event')
        ->fill('summary', 'Dentist')
        ->fill('starts_at', '2026-06-12T10:00')
        ->fill('ends_at', '2026-06-12T11:00')
        ->click('Create event')
        ->assertSee('Dentist');

    expect(DavCalendarObject::query()->where('summary', 'Dentist')->exists())->toBeTrue();
});

it('preserves the dragged time range when creating an event', function () {
    $user = User::factory()->create();
    $calendar = DavCalendar::factory()->for($user)->create(['display_name' => 'Personal']);
    DavCalendarObject::factory()->for($calendar, 'calendar')->create([
        'summary' => 'Existing appointment',
        'starts_at' => today()->setTime(8, 0),
        'ends_at' => today()->setTime(9, 0),
        'is_all_day' => false,
    ]);

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

    expect(DavCalendar::query()->where('user_id', $user->id)->where('display_name', 'Side Projects')->exists())->toBeTrue();
});
