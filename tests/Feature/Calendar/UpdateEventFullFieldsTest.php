<?php

use App\Models\User;
use Bambamboole\LaravelDav\Models\DavCalendar;
use Bambamboole\LaravelDav\Models\DavCalendarObject;

it('updates event time and status through the edit endpoint', function () {
    $user = User::factory()->create();
    $calendar = DavCalendar::factory()->for($user)->create();
    $event = DavCalendarObject::factory()->for($calendar, 'calendar')->state(davData(['summary' => 'Old']))->create();

    $this->actingAs($user)
        ->put("/calendar/events/{$event->id}", [
            'summary' => 'New',
            'starts_at' => '2026-07-01T08:00:00Z',
            'ends_at' => '2026-07-01T09:00:00Z',
            'is_all_day' => false,
            'status' => 'CONFIRMED',
            'expected_etag' => $event->etag,
        ])
        ->assertRedirect();

    $event->refresh();
    expect($event->data->summary)->toBe('New')
        ->and($event->starts_at->toIso8601String())->toContain('2026-07-01T08:00:00')
        ->and($event->calendar_data)->toContain('STATUS:CONFIRMED')
        ->and($event->calendar_data)->toContain('DTSTART:20260701T080000Z');
});
