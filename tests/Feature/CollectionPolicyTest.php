<?php

use App\Models\User;
use Bambamboole\LaravelDav\Models\DavAddressBook;
use Bambamboole\LaravelDav\Models\DavCalendar;

it('lets owners and admins manage a calendar, but not other members', function () {
    $owner = User::factory()->create();
    $calendar = DavCalendar::factory()->for($owner)->create();

    $admin = User::factory()->admin()->create();
    $stranger = User::factory()->create();

    expect($owner->can('update', $calendar))->toBeTrue()
        ->and($owner->can('delete', $calendar))->toBeTrue()
        ->and($admin->can('update', $calendar))->toBeTrue()
        ->and($stranger->can('update', $calendar))->toBeFalse();
});

it('applies the same rule to address books', function () {
    $owner = User::factory()->create();
    $book = DavAddressBook::factory()->for($owner)->create();
    $stranger = User::factory()->create();
    $admin = User::factory()->admin()->create();

    expect($owner->can('update', $book))->toBeTrue()
        ->and($admin->can('delete', $book))->toBeTrue()
        ->and($stranger->can('delete', $book))->toBeFalse();
});
