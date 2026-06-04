<?php

use App\Actions\Calendar\UpdateCalendarObject;
use App\Exceptions\StaleEntryException;
use Bambamboole\LaravelDav\Models\DavCalendar;
use Bambamboole\LaravelDav\Models\DavCalendarObject;
use Bambamboole\LaravelDav\Models\DavChange;

it('updates modeled fields, regenerates payload, bumps etag, and records a change', function () {
    $calendar = DavCalendar::factory()->create();
    $object = DavCalendarObject::factory()->for($calendar, 'calendar')->create(['summary' => 'Old']);
    $originalEtag = $object->etag;
    $originalToken = $calendar->sync_token;

    $updated = app(UpdateCalendarObject::class)->handle($object, ['summary' => 'New title'], $originalEtag);

    expect($updated->summary)->toBe('New title')
        ->and($updated->etag)->not->toBe($originalEtag)
        ->and($updated->calendar_data)->toContain('SUMMARY:New title')
        ->and($calendar->refresh()->sync_token)->toBe($originalToken + 1)
        ->and(DavChange::query()->where('collection_id', $calendar->id)->where('operation', 2)->exists())->toBeTrue();
});

it('rejects a stale etag', function () {
    $object = DavCalendarObject::factory()->create();

    expect(fn () => app(UpdateCalendarObject::class)->handle($object, ['summary' => 'X'], 'not-the-etag'))
        ->toThrow(StaleEntryException::class);
});

it('preserves unmodeled iCalendar content when updating', function () {
    $calendar = DavCalendar::factory()->create();
    $raw = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nBEGIN:VEVENT\r\nUID:keep\r\nSUMMARY:Old\r\nDTSTART:20260603T090000Z\r\nDTEND:20260603T100000Z\r\nX-CUSTOM:keepme\r\nBEGIN:VALARM\r\nACTION:DISPLAY\r\nTRIGGER:-PT15M\r\nEND:VALARM\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
    $object = DavCalendarObject::factory()->for($calendar, 'calendar')->create([
        'uid' => 'keep',
        'summary' => 'Old',
        'calendar_data' => $raw,
    ]);

    $updated = app(UpdateCalendarObject::class)->handle($object, ['summary' => 'New'], $object->etag);

    expect($updated->calendar_data)->toContain('SUMMARY:New')
        ->and($updated->calendar_data)->toContain('X-CUSTOM:keepme')
        ->and($updated->calendar_data)->toContain('BEGIN:VALARM')
        ->and($updated->calendar_data)->toContain('TRIGGER:-PT15M');
});
