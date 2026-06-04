<?php

use App\Models\User;
use Bambamboole\LaravelDav\Models\DavAddressBook;
use Bambamboole\LaravelDav\Models\DavCard;
use Bambamboole\LaravelDav\Models\DavChange;

it('deletes a contact the user owns and records a delete change', function () {
    $user = User::factory()->create();
    $book = DavAddressBook::factory()->for($user)->create();
    $card = DavCard::factory()->for($book, 'addressBook')->create();

    $this->actingAs($user)
        ->delete("/contacts/{$card->id}", ['expected_etag' => $card->etag])
        ->assertRedirect()
        ->assertInertiaFlash('toast.type', 'success')
        ->assertInertiaFlash('toast.message', 'Contact deleted.');

    expect(DavCard::query()->whereKey($card->id)->exists())->toBeFalse();
    expect(DavChange::query()->where('collection_id', $book->id)->where('operation', 3)->exists())->toBeTrue();
});

it('forbids deleting another user\'s contact', function () {
    $user = User::factory()->create();
    $card = DavCard::factory()->for(DavAddressBook::factory()->for(User::factory()), 'addressBook')->create();

    $this->actingAs($user)
        ->delete("/contacts/{$card->id}", ['expected_etag' => $card->etag])
        ->assertForbidden();
});
