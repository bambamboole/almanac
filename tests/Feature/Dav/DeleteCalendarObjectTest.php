<?php

use App\Actions\Calendar\DeleteCalendarObject;
use App\Exceptions\StaleEntryException;
use Bambamboole\LaravelDav\Models\DavCalendar;
use Bambamboole\LaravelDav\Models\DavCalendarObject;
use Bambamboole\LaravelDav\Models\DavChange;

it('deletes an object and records a delete change', function () {
    $calendar = DavCalendar::factory()->create();
    $object = DavCalendarObject::factory()->for($calendar, 'calendar')->create();
    $token = $calendar->sync_token;

    app(DeleteCalendarObject::class)->handle($object, $object->etag);

    expect(DavCalendarObject::query()->whereKey($object->getKey())->exists())->toBeFalse()
        ->and($calendar->refresh()->sync_token)->toBe($token + 1)
        ->and(DavChange::query()->where('collection_id', $calendar->id)->where('operation', 3)->exists())->toBeTrue();
});

it('rejects a stale etag and keeps the object', function () {
    $object = DavCalendarObject::factory()->create();

    expect(fn () => app(DeleteCalendarObject::class)->handle($object, 'stale'))
        ->toThrow(StaleEntryException::class);

    expect(DavCalendarObject::query()->whereKey($object->getKey())->exists())->toBeTrue();
});
