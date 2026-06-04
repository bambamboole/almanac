<?php

use App\Models\User;
use Bambamboole\LaravelDav\Models\DavAddressBook;
use Bambamboole\LaravelDav\Models\DavCard;

it('deletes a contact from the UI', function () {
    $user = User::factory()->create();
    $book = DavAddressBook::factory()->for($user)->create();
    DavCard::factory()->for($book, 'addressBook')->create(['full_name' => 'Temp Person']);

    $this->actingAs($user);
    $page = visit('/contacts');

    $page->assertSee('Temp Person')
        ->assertNoJavaScriptErrors()
        ->click('[data-contact-actions]')
        ->click('Delete')
        ->click('Delete contact');

    expect(DavCard::query()->where('full_name', 'Temp Person')->exists())->toBeFalse();
});
