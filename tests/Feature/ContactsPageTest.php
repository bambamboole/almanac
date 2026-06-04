<?php

use App\Models\User;
use Bambamboole\LaravelDav\Models\DavAddressBook;
use Bambamboole\LaravelDav\Models\DavCard;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

test('guests cannot access the contacts page', function () {
    $this->get('/contacts')->assertRedirect('/login');
});

test('authenticated user gets the contacts page payload', function () {
    $user = User::factory()->create();
    $addressBook = DavAddressBook::factory()->for($user)->create([
        'display_name' => 'People',
        'description' => 'Personal contacts',
    ]);

    DavCard::factory()->for($addressBook, 'addressBook')->create([
        'full_name' => 'Ada Lovelace',
        'given_name' => 'Ada',
        'family_name' => 'Lovelace',
        'organization' => 'Analytical Engines',
        'emails' => ['ada@example.com', 'work@example.com'],
        'phones' => ['+1 555 0100'],
        'card_data' => 'raw vcard data should not be exposed',
    ]);

    $this->actingAs($user)
        ->get('/contacts')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('contacts/index')
            ->has('addressBooks', 1, fn (Assert $page) => $page
                ->where('id', $addressBook->id)
                ->where('name', 'People')
                ->where('description', 'Personal contacts')
                ->where('contacts_count', 1)
                ->etc(),
            )
            ->has('contacts', 1, fn (Assert $page) => $page
                ->where('full_name', 'Ada Lovelace')
                ->where('display_name', 'Ada Lovelace')
                ->where('given_name', 'Ada')
                ->where('family_name', 'Lovelace')
                ->where('organization', 'Analytical Engines')
                ->where('emails.0', 'ada@example.com')
                ->where('emails.1', 'work@example.com')
                ->where('phones.0', '+1 555 0100')
                ->where('primary_email', 'ada@example.com')
                ->where('primary_phone', '+1 555 0100')
                ->where('address_book.id', $addressBook->id)
                ->where('address_book.name', 'People')
                ->missing('card_data')
                ->etc(),
            ),
        );
});

test('contacts page excludes another users contacts', function () {
    $user = User::factory()->create();
    $addressBook = DavAddressBook::factory()->for($user)->create();
    $ownedContact = DavCard::factory()->for($addressBook, 'addressBook')->create([
        'full_name' => 'Owned Contact',
    ]);

    DavCard::factory()->create([
        'full_name' => 'Other Contact',
    ]);

    $contacts = $this->actingAs($user)
        ->get('/contacts')
        ->inertiaProps('contacts');

    expect($contacts)->toHaveCount(1)
        ->and($contacts[0]['id'])->toBe($ownedContact->id)
        ->and($contacts[0]['full_name'])->toBe('Owned Contact');
});

test('contact props do not expose raw card data', function () {
    $user = User::factory()->create();
    $addressBook = DavAddressBook::factory()->for($user)->create();

    DavCard::factory()->for($addressBook, 'addressBook')->create([
        'card_data' => 'BEGIN:VCARD',
    ]);

    $contact = $this->actingAs($user)
        ->get('/contacts')
        ->inertiaProps('contacts.0');

    expect($contact)->not->toHaveKey('card_data');
});
