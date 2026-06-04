<?php

use App\Models\User;
use Bambamboole\LaravelDav\Models\DavCalendar;

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

    $calendar = DavCalendar::query()->where('user_id', $this->user->id)->where('display_name', 'Travel')->firstOrFail();
    expect($calendar->components)->toContain('VJOURNAL')
        ->and($calendar->uri)->not->toBeEmpty();
});

it('updates a calendar the user owns', function () {
    $calendar = DavCalendar::factory()->for($this->user)->create(['display_name' => 'Old']);

    $this->actingAs($this->user)
        ->patch("/calendar/calendars/{$calendar->id}", [
            'display_name' => 'New',
            'components' => ['VEVENT'],
        ])
        ->assertRedirect()
        ->assertInertiaFlash('toast.type', 'success')
        ->assertInertiaFlash('toast.message', 'Calendar updated.');

    expect($calendar->fresh()->display_name)->toBe('New');
});

it('forbids editing another user\'s calendar', function () {
    $calendar = DavCalendar::factory()->for(User::factory())->create();

    $this->actingAs($this->user)
        ->patch("/calendar/calendars/{$calendar->id}", ['display_name' => 'Hacked', 'components' => ['VEVENT']])
        ->assertForbidden();
});

it('deletes a calendar the user owns', function () {
    $calendar = DavCalendar::factory()->for($this->user)->create();

    $this->actingAs($this->user)
        ->delete("/calendar/calendars/{$calendar->id}")
        ->assertRedirect()
        ->assertInertiaFlash('toast.type', 'success')
        ->assertInertiaFlash('toast.message', 'Calendar deleted.');

    expect(DavCalendar::query()->whereKey($calendar->id)->exists())->toBeFalse();
});
