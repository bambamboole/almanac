<?php

use App\Models\User;
use Bambamboole\LaravelDav\Models\DavAddressBook;
use Bambamboole\LaravelDav\Models\DavCalendar;

it('edits a calendar from the calendar page', function () {
    $user = User::factory()->create();
    DavCalendar::factory()->for($user)->create(['display_name' => 'Work', 'components' => ['VEVENT']]);

    $this->actingAs($user);
    $page = visit('/calendar');

    $page->assertSee('Work')
        ->assertNoJavaScriptErrors()
        ->click('[data-calendar-actions]')
        ->click('[data-edit-calendar]')
        ->fill('edit_display_name', 'Work & Side')
        ->click('Save calendar')
        ->assertSee('Work & Side');

    expect(DavCalendar::query()->where('user_id', $user->id)->where('display_name', 'Work & Side')->exists())->toBeTrue();
});

it('deletes a calendar from the calendar page', function () {
    $user = User::factory()->create();
    DavCalendar::factory()->for($user)->create(['display_name' => 'Work', 'components' => ['VEVENT']]);

    $this->actingAs($user);
    $page = visit('/calendar');

    $page->assertSee('Work')
        ->assertNoJavaScriptErrors()
        ->click('[data-calendar-actions]')
        ->click('[data-delete-calendar]')
        ->assertSee('Delete calendar?')
        ->click('Delete calendar')
        ->assertDontSee('Work');

    expect(DavCalendar::query()->where('user_id', $user->id)->where('display_name', 'Work')->exists())->toBeFalse();
});

it('edits an address book from the contacts page', function () {
    $user = User::factory()->create();
    DavAddressBook::factory()->for($user)->create(['display_name' => 'Friends']);

    $this->actingAs($user);
    $page = visit('/contacts');

    $page->assertSee('Friends')
        ->assertNoJavaScriptErrors()
        ->click('[data-address-book-actions]')
        ->click('[data-edit-address-book]')
        ->fill('edit_address_book_display_name', 'Inner Circle')
        ->click('Save address book')
        ->assertSee('Inner Circle');

    expect(DavAddressBook::query()->where('user_id', $user->id)->where('display_name', 'Inner Circle')->exists())->toBeTrue();
});

it('deletes an address book from the contacts page', function () {
    $user = User::factory()->create();
    DavAddressBook::factory()->for($user)->create(['display_name' => 'Friends']);

    $this->actingAs($user);
    $page = visit('/contacts');

    $page->assertSee('Friends')
        ->assertNoJavaScriptErrors()
        ->click('[data-address-book-actions]')
        ->click('[data-delete-address-book]')
        ->assertSee('Delete address book?')
        ->click('Delete address book')
        ->assertDontSee('Friends');

    expect(DavAddressBook::query()->where('user_id', $user->id)->where('display_name', 'Friends')->exists())->toBeFalse();
});
