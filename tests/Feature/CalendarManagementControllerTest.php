<?php

use App\Models\User;
use Bambamboole\LaravelDav\Models\DavCalendar;
use Bambamboole\LaravelDav\Models\DavCalendarInstance;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('creates a calendar for the current user', function () {
    $this->actingAs($this->user)
        ->post('/calendar/calendars', [
            'display_name' => 'Travel',
            'description' => 'Trips',
            'color' => '#FF8800FF',
            'components' => ['VEVENT', 'VJOURNAL'],
        ])
        ->assertRedirect()
        ->assertInertiaFlash('toast.type', 'success')
        ->assertInertiaFlash('toast.message', 'Calendar created.');

    $calendarInstance = DavCalendarInstance::query()->where('owner_id', $this->user->id)->where('display_name', 'Travel')->firstOrFail();
    expect($calendarInstance->calendar->components)->toContain('VJOURNAL')
        ->and($calendarInstance->uri)->not->toBeEmpty();
});

it('updates a calendar the user owns', function () {
    $calendar = davCalendarFor($this->user, ['display_name' => 'Old']);
    $calendarInstance = $calendar->ownerInstance()->firstOrFail();

    $this->actingAs($this->user)
        ->patch("/calendar/calendars/{$calendarInstance->id}", [
            'display_name' => 'New',
            'components' => ['VEVENT'],
        ])
        ->assertRedirect()
        ->assertInertiaFlash('toast.type', 'success')
        ->assertInertiaFlash('toast.message', 'Calendar updated.');

    expect($calendar->fresh()->ownerInstance?->display_name)->toBe('New');
});

it('updates a shared calendar instance without changing the owner instance', function () {
    $owner = User::factory()->create();
    $calendar = davCalendarFor($owner, ['display_name' => 'Owner calendar', 'color' => '#111111', 'components' => ['VEVENT']]);
    $sharedInstance = $calendar->shareWith($this->user, DavCalendarInstance::AccessRead);

    $this->actingAs($this->user)
        ->patch("/calendar/calendars/{$sharedInstance->id}", [
            'display_name' => 'My planning',
            'color' => '#abcdef',
            'components' => ['VEVENT', 'VJOURNAL'],
        ])
        ->assertRedirect()
        ->assertInertiaFlash('toast.type', 'success')
        ->assertInertiaFlash('toast.message', 'Calendar updated.');

    expect($sharedInstance->refresh()->display_name)->toBe('My planning')
        ->and($sharedInstance->color)->toBe('#abcdef')
        ->and($calendar->fresh()->ownerInstance?->display_name)->toBe('Owner calendar')
        ->and($calendar->components)->not->toContain('VJOURNAL');
});

it('forbids editing another user\'s calendar', function () {
    $calendar = davCalendarFor(User::factory());
    $calendarInstance = $calendar->ownerInstance()->firstOrFail();

    $this->actingAs($this->user)
        ->patch("/calendar/calendars/{$calendarInstance->id}", ['display_name' => 'Hacked', 'components' => ['VEVENT']])
        ->assertForbidden();
});

it('deletes a calendar the user owns', function () {
    $calendar = davCalendarFor($this->user);
    $calendarInstance = $calendar->ownerInstance()->firstOrFail();

    $this->actingAs($this->user)
        ->delete("/calendar/calendars/{$calendarInstance->id}")
        ->assertRedirect()
        ->assertInertiaFlash('toast.type', 'success')
        ->assertInertiaFlash('toast.message', 'Calendar deleted.');

    expect(DavCalendar::query()->whereKey($calendar->id)->exists())->toBeFalse();
});

it('removes a shared calendar instance without deleting the backing calendar', function () {
    $owner = User::factory()->create();
    $calendar = davCalendarFor($owner);
    $sharedInstance = $calendar->shareWith($this->user, DavCalendarInstance::AccessRead);

    $this->actingAs($this->user)
        ->delete("/calendar/calendars/{$sharedInstance->id}")
        ->assertRedirect()
        ->assertInertiaFlash('toast.type', 'success')
        ->assertInertiaFlash('toast.message', 'Calendar removed.');

    expect(DavCalendarInstance::query()->whereKey($sharedInstance->id)->exists())->toBeFalse()
        ->and(DavCalendar::query()->whereKey($calendar->id)->exists())->toBeTrue()
        ->and($calendar->fresh()->ownerInstance)->not->toBeNull();
});
