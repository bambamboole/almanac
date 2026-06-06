<?php

use App\Models\User;
use Bambamboole\LaravelDav\Models\DavCalendar;
use Bambamboole\LaravelDav\Models\DavCalendarInstance;
use Bambamboole\LaravelDav\Models\DavCalendarObject;

test('guests cannot export the calendar', function () {
    $this->get('/calendar/export')->assertRedirect('/login');
});

test('authenticated user downloads their calendar as an ics file', function () {
    $user = User::factory()->create();
    $calendar = davCalendarFor($user);

    $event = DavCalendarObject::factory()->for($calendar, 'calendar')->state(davData(['summary' => 'Planning review']))->create([
        'starts_at' => now()->addDay()->setTime(9, 0),
        'ends_at' => now()->addDay()->setTime(10, 0),
    ]);

    $response = $this->actingAs($user)->get('/calendar/export');

    $response->assertOk();

    expect($response->headers->get('Content-Type'))->toContain('text/calendar');
    expect($response->headers->get('Content-Disposition'))->toContain('attachment')
        ->toContain('calendar.ics');

    $body = $response->getContent();

    expect($body)->toContain('BEGIN:VCALENDAR')
        ->toContain('END:VCALENDAR')
        ->toContain($event->uid);
});

test('calendar export only includes the current users events', function () {
    $user = User::factory()->create();
    $calendar = davCalendarFor($user);
    $ownEvent = DavCalendarObject::factory()->for($calendar, 'calendar')->state(davData(['summary' => 'Owned event']))->create();

    $otherCalendar = DavCalendar::factory()->create();
    $otherEvent = DavCalendarObject::factory()->for($otherCalendar, 'calendar')->state(davData(['summary' => 'Other user event']))->create();

    $body = $this->actingAs($user)->get('/calendar/export')->getContent();

    expect($body)->toContain($ownEvent->uid)
        ->not->toContain($otherEvent->uid);
});

test('calendar export includes shared calendar events', function () {
    $owner = User::factory()->create();
    $recipient = User::factory()->create();
    $calendar = davCalendarFor($owner);
    $calendar->shareWith($recipient, DavCalendarInstance::AccessRead);
    $event = DavCalendarObject::factory()->for($calendar, 'calendar')->state(davData(['summary' => 'Shared event']))->create();

    $body = $this->actingAs($recipient)->get('/calendar/export')->getContent();

    expect($body)->toContain($event->uid);
});

test('calendar export can be scoped to a single calendar', function () {
    $user = User::factory()->create();
    $workCalendar = davCalendarFor($user, ['display_name' => 'Work']);
    $homeCalendar = davCalendarFor($user, ['display_name' => 'Home']);
    $workCalendarInstance = $workCalendar->ownerInstance()->firstOrFail();
    $workEvent = DavCalendarObject::factory()->for($workCalendar, 'calendar')->state(davData(['summary' => 'Work event']))->create();
    $homeEvent = DavCalendarObject::factory()->for($homeCalendar, 'calendar')->state(davData(['summary' => 'Home event']))->create();

    $response = $this->actingAs($user)->get("/calendar/calendars/{$workCalendarInstance->id}/export");

    $response->assertOk();

    expect($response->headers->get('Content-Disposition'))->toContain('work.ics');

    $body = $response->getContent();

    expect($body)->toContain($workEvent->uid)
        ->not->toContain($homeEvent->uid);
});

test('calendar export cannot download another users calendar', function () {
    $user = User::factory()->create();
    $otherCalendar = davCalendarFor(User::factory()->create());
    $otherCalendarInstance = $otherCalendar->ownerInstance()->firstOrFail();

    $this->actingAs($user)
        ->get("/calendar/calendars/{$otherCalendarInstance->id}/export")
        ->assertForbidden();
});

test('calendar export can be scoped to a shared calendar', function () {
    $owner = User::factory()->create();
    $recipient = User::factory()->create();
    $calendar = davCalendarFor($owner, ['display_name' => 'Owner calendar']);
    $instance = $calendar->shareWith($recipient, DavCalendarInstance::AccessRead);
    $instance->updateDavProperties(['display_name' => 'Shared planning']);
    $event = DavCalendarObject::factory()->for($calendar, 'calendar')->state(davData(['summary' => 'Shared export']))->create();

    $response = $this->actingAs($recipient)->get("/calendar/calendars/{$instance->id}/export");

    $response->assertOk();

    expect($response->headers->get('Content-Disposition'))->toContain('shared-planning.ics');
    expect($response->getContent())->toContain($event->uid);
});

test('calendar export returns a valid empty calendar when there are no events', function () {
    $user = User::factory()->create();

    $body = $this->actingAs($user)->get('/calendar/export')
        ->assertOk()
        ->getContent();

    expect($body)->toContain('BEGIN:VCALENDAR')
        ->toContain('END:VCALENDAR');
});

test('calendar export de-duplicates shared timezones across objects', function () {
    $user = User::factory()->create();
    $calendar = davCalendarFor($user);

    $payload = <<<'ICS'
    BEGIN:VCALENDAR
    VERSION:2.0
    PRODID:-//Test//Test//EN
    BEGIN:VTIMEZONE
    TZID:Europe/Berlin
    BEGIN:STANDARD
    TZOFFSETFROM:+0200
    TZOFFSETTO:+0100
    TZNAME:CET
    DTSTART:19701025T030000
    END:STANDARD
    END:VTIMEZONE
    BEGIN:VEVENT
    UID:%s
    DTSTART;TZID=Europe/Berlin:20260601T090000
    DTEND;TZID=Europe/Berlin:20260601T100000
    SUMMARY:%s
    END:VEVENT
    END:VCALENDAR
    ICS;

    DavCalendarObject::factory()->for($calendar, 'calendar')->create([
        'uid' => 'event-one',
        'calendar_data' => str_replace("\n", "\r\n", sprintf($payload, 'event-one', 'First')),
    ]);

    DavCalendarObject::factory()->for($calendar, 'calendar')->create([
        'uid' => 'event-two',
        'calendar_data' => str_replace("\n", "\r\n", sprintf($payload, 'event-two', 'Second')),
    ]);

    $body = $this->actingAs($user)->get('/calendar/export')->getContent();

    expect(substr_count($body, 'TZID:Europe/Berlin'))->toBe(1);
    expect($body)->toContain('event-one')->toContain('event-two');
});
