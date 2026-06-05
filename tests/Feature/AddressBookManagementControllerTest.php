<?php

use App\Models\User;
use Bambamboole\LaravelDav\Models\DavAddressBook;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('creates an address book for the current user', function () {
    $this->actingAs($this->user)
        ->post('/contacts/address-books', ['display_name' => 'Work', 'description' => 'Colleagues'])
        ->assertRedirect()
        ->assertInertiaFlash('toast.type', 'success')
        ->assertInertiaFlash('toast.message', 'Address book created.');

    expect(DavAddressBook::query()->where('owner_id', $this->user->id)->where('display_name', 'Work')->exists())->toBeTrue();
});

it('updates and deletes an owned address book', function () {
    $book = DavAddressBook::factory()->for($this->user, 'owner')->create(['display_name' => 'Old']);

    $this->actingAs($this->user)
        ->patch("/contacts/address-books/{$book->id}", ['display_name' => 'New'])
        ->assertRedirect()
        ->assertInertiaFlash('toast.type', 'success')
        ->assertInertiaFlash('toast.message', 'Address book updated.');

    expect($book->fresh()->display_name)->toBe('New');

    $this->actingAs($this->user)
        ->delete("/contacts/address-books/{$book->id}")
        ->assertRedirect()
        ->assertInertiaFlash('toast.type', 'success')
        ->assertInertiaFlash('toast.message', 'Address book deleted.');

    expect(DavAddressBook::query()->whereKey($book->id)->exists())->toBeFalse();
});

it('forbids editing another user\'s address book', function () {
    $book = DavAddressBook::factory()->for(User::factory(), 'owner')->create();

    $this->actingAs($this->user)->patch("/contacts/address-books/{$book->id}", ['display_name' => 'X'])->assertForbidden();
});
