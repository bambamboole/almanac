<?php

use App\Actions\Calendar\CreateCalendarObject;
use App\Models\User;
use Bambamboole\LaravelDav\Models\DavCalendar;
use Bambamboole\LaravelDav\Models\DavChange;
use Illuminate\Support\Carbon;

it('creates an event, serializes a payload, and records an added change', function () {
    $user = User::factory()->create();
    $calendar = DavCalendar::factory()->for($user)->create(['timezone' => 'Europe/Berlin', 'sync_token' => 1]);

    $start = Carbon::parse('2026-06-10 09:00:00', 'UTC');

    $event = app(CreateCalendarObject::class)->handle($calendar, [
        'summary' => 'Standup',
        'description' => 'Daily sync',
        'location' => 'Office',
        'starts_at' => $start,
        'ends_at' => $start->copy()->addMinutes(30),
        'is_all_day' => false,
    ]);

    expect($event->uid)->not->toBeEmpty()
        ->and($event->uri)->toBe($event->uid.'.ics')
        ->and($event->component_type)->toBe('VEVENT')
        ->and($event->calendar_data)->toContain('SUMMARY:Standup')
        ->and($event->calendar_data)->toContain('BEGIN:VEVENT')
        ->and($event->etag)->not->toBeEmpty();

    expect($calendar->refresh()->sync_token)->toBe(2);
    expect(DavChange::query()->where('collection_id', $calendar->id)->where('operation', 1)->where('resource_uri', $event->uri)->exists())->toBeTrue();
});
