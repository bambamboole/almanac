<?php

use App\Actions\Dav\CreateDavCredential;
use App\Events\DavCollectionChanged;
use App\Events\DavCollectionChanged as DavCollectionChangedEvent;
use App\Models\User;
use Bambamboole\LaravelDav\Models\DavCalendar;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Facades\Event;

it('dispatches a broadcast when an object is written through the dav server', function () {
    Event::fake([DavCollectionChangedEvent::class]);

    $user = User::factory()->create();
    $credential = app(CreateDavCredential::class)->handle($user, 'Phone');
    $authHeader = calDavAuthHeader($credential['username'], $credential['plainSecret']);

    calDavPut(
        $this,
        '/dav/calendars/'.$user->id.'/personal/planning.ics',
        $authHeader,
        calDavPayload('VEVENT', [
            'UID' => 'x',
            'DTSTAMP' => '20260603T000000Z',
            'SUMMARY' => 'Plan',
            'DTSTART' => '20260603T090000Z',
            'DTEND' => '20260603T100000Z',
        ]),
    )->assertSuccessful();

    Event::assertDispatched(DavCollectionChangedEvent::class, fn (DavCollectionChangedEvent $event): bool => $event->collectionType === 'calendar' && $event->operation === 'added' && $event->resourceUri === 'planning.ics' && $event->userId === $user->id);
});

it('authorizes the dav channel only for the owning user', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    // Resolve the registered channel callback directly from the broadcaster,
    // bypassing the HTTP auth endpoint which is a no-op in the null test driver.
    $broadcaster = app(BroadcastManager::class)->driver();
    $channels = (fn () => $this->channels)->bindTo($broadcaster, $broadcaster)();

    $callback = null;
    foreach ($channels as $pattern => $cb) {
        if (str_contains($pattern, 'dav')) {
            $callback = $cb;
            break;
        }
    }

    expect($callback)->not->toBeNull('dav.{userId} channel should be registered');
    expect($callback($owner, (int) $owner->id))->toBeTrue();
    expect($callback($other, (int) $owner->id))->toBeFalse();
});

it('broadcasts on the owning user private dav channel', function () {
    $calendar = DavCalendar::factory()->create();

    $event = new DavCollectionChanged(
        userId: $calendar->user_id,
        collectionType: 'calendar',
        collectionId: $calendar->id,
        resourceUri: 'event-1.ics',
        operation: 'modified',
        syncToken: 5,
    );

    expect($event->broadcastOn())->toBeInstanceOf(PrivateChannel::class)
        ->and($event->broadcastOn()->name)->toBe('private-dav.'.$calendar->user_id)
        ->and($event->broadcastAs())->toBe('dav.changed')
        ->and($event->broadcastWith())->toMatchArray([
            'type' => 'calendar',
            'collection_id' => $calendar->id,
            'uri' => 'event-1.ics',
            'operation' => 'modified',
            'sync_token' => 5,
        ]);
});
