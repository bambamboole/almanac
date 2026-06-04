<?php

use App\Actions\Dav\CreateDavCredential;
use App\Models\User;
use Bambamboole\LaravelDav\Models\DavCalendar;
use Bambamboole\LaravelDav\Models\DavCalendarObject;
use Illuminate\Support\Carbon;

it('stores fetches and deletes calendar objects through caldav', function () {
    $user = User::factory()->create();
    $credential = app(CreateDavCredential::class)->handle($user, 'Phone');
    $payload = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Almanac//Tests//EN\r\nBEGIN:VEVENT\r\nUID:event-1\r\nDTSTAMP:20260603T000000Z\r\nSUMMARY:Deep Work\r\nDTSTART:20260603T070000Z\r\nDTEND:20260603T083000Z\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
    $path = '/dav/calendars/'.$user->id.'/personal/event-1.ics';
    $authHeader = calDavAuthHeader($credential['username'], $credential['plainSecret']);

    calDavPut($this, $path, $authHeader, $payload)->assertSuccessful();

    expect(DavCalendarObject::query()->where('uri', 'event-1.ics')->first())
        ->not->toBeNull()
        ->summary->toBe('Deep Work')
        ->uid->toBe('event-1')
        ->component_type->toBe('VEVENT')
        ->calendar_data->toBe($payload);

    $this->withHeaders(['Authorization' => $authHeader])
        ->get($path)
        ->assertSuccessful()
        ->assertContent($payload);

    $this->withHeaders(['Authorization' => $authHeader])
        ->delete($path)
        ->assertSuccessful();

    $this->withHeaders(['Authorization' => $authHeader])
        ->get($path)
        ->assertNotFound();
});

it('updates existing calendar objects through caldav', function () {
    try {
        Carbon::setTestNow('2026-06-03 09:00:00');

        $user = User::factory()->create();
        $credential = app(CreateDavCredential::class)->handle($user, 'Phone');
        $path = '/dav/calendars/'.$user->id.'/personal/event-1.ics';
        $authHeader = calDavAuthHeader($credential['username'], $credential['plainSecret']);
        $originalPayload = calDavPayload('VEVENT', [
            'UID' => 'event-1',
            'DTSTAMP' => '20260603T000000Z',
            'SUMMARY' => 'Deep Work',
            'DTSTART' => '20260603T070000Z',
            'DTEND' => '20260603T083000Z',
        ]);
        $updatedPayload = calDavPayload('VEVENT', [
            'UID' => 'event-1',
            'DTSTAMP' => '20260603T000000Z',
            'SUMMARY' => 'Planning',
            'LOCATION' => 'Office',
            'DTSTART' => '20260603T090000Z',
            'DTEND' => '20260603T100000Z',
        ]);

        calDavPut($this, $path, $authHeader, $originalPayload)->assertSuccessful();

        $object = DavCalendarObject::query()->where('uri', 'event-1.ics')->firstOrFail();
        $originalSize = $object->size;
        $originalLastModifiedAt = $object->last_modified_at;

        Carbon::setTestNow('2026-06-03 10:00:00');

        calDavPut($this, $path, $authHeader, $updatedPayload)->assertSuccessful();

        $object->refresh();

        expect(DavCalendarObject::query()->where('uri', 'event-1.ics')->count())->toBe(1)
            ->and($object->summary)->toBe('Planning')
            ->and($object->location)->toBe('Office')
            ->and($object->starts_at?->toIso8601String())->toBe('2026-06-03T09:00:00+00:00')
            ->and($object->ends_at?->toIso8601String())->toBe('2026-06-03T10:00:00+00:00')
            ->and($object->etag)->toBe(sha1($updatedPayload))
            ->and($object->etag)->not->toBe(sha1($originalPayload))
            ->and($object->size)->toBe(strlen($updatedPayload))
            ->and($object->size)->not->toBe($originalSize)
            ->and($object->last_modified_at?->toIso8601String())->toBe('2026-06-03T10:00:00+00:00')
            ->and($object->last_modified_at?->eq($originalLastModifiedAt))->toBeFalse()
            ->and($object->calendar_data)->toBe($updatedPayload);
    } finally {
        Carbon::setTestNow();
    }
});

it('does not allow a dav credential to access another users calendar objects', function (string $method) {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();
    app(CreateDavCredential::class)->handle($owner, 'Laptop');
    $attackerCredential = app(CreateDavCredential::class)->handle($attacker, 'Phone');
    $ownerCalendar = DavCalendar::query()->whereBelongsTo($owner, 'user')->where('uri', 'personal')->firstOrFail();
    $existingObject = DavCalendarObject::factory()->for($ownerCalendar, 'calendar')->create([
        'uri' => 'event-1.ics',
        'summary' => 'Private',
    ]);
    $path = '/dav/calendars/'.$owner->id.'/personal/event-1.ics';
    $authHeader = calDavAuthHeader($attackerCredential['username'], $attackerCredential['plainSecret']);
    $payload = calDavPayload('VEVENT', [
        'UID' => 'event-1',
        'DTSTAMP' => '20260603T000000Z',
        'SUMMARY' => 'Overwrite',
        'DTSTART' => '20260603T070000Z',
        'DTEND' => '20260603T083000Z',
    ]);

    $response = match ($method) {
        'GET' => $this->withHeaders(['Authorization' => $authHeader])->get($path),
        'PUT' => calDavPut($this, $path, $authHeader, $payload),
        'DELETE' => $this->withHeaders(['Authorization' => $authHeader])->delete($path),
    };

    expect($response->getStatusCode())->toBeIn([403, 404])
        ->and(DavCalendarObject::query()->where('dav_calendar_id', $ownerCalendar->id)->count())->toBe(1)
        ->and($existingObject->refresh()->summary)->toBe('Private')
        ->and(DavCalendarObject::query()->where('summary', 'Overwrite')->exists())->toBeFalse();
})->with(['GET', 'PUT', 'DELETE']);
