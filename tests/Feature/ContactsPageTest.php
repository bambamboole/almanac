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

    DavCard::factory()->for($addressBook, 'addressBook')->state(davData([
        'formattedName' => 'Ada Lovelace',
        'givenName' => 'Ada',
        'familyName' => 'Lovelace',
        'organization' => 'Analytical Engines',
        'emailAddresses' => [
            ['label' => 'home', 'value' => 'ada@example.com', 'types' => ['INTERNET']],
            ['label' => 'work', 'value' => 'work@example.com', 'types' => ['INTERNET']],
        ],
        'phoneNumbers' => [['label' => 'mobile', 'value' => '+1 555 0100', 'types' => ['CELL']]],
    ]))->create(['card_data' => 'raw vcard data should not be exposed']);

    $this->actingAs($user)
        ->get('/contacts')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('contacts/index')
            ->has('addressBooks', 1, fn (Assert $page) => $page
                ->where('id', $addressBook->id)
                ->where('display_name', 'People')
                ->where('description', 'Personal contacts')
                ->where('cards_count', 1)
                ->etc(),
            )
            ->has('contacts', 1, fn (Assert $page) => $page
                ->where('data.formattedName', 'Ada Lovelace')
                ->where('data.givenName', 'Ada')
                ->where('data.familyName', 'Lovelace')
                ->where('data.organization', 'Analytical Engines')
                ->where('data.emailAddresses.0.value', 'ada@example.com')
                ->where('data.emailAddresses.1.value', 'work@example.com')
                ->where('data.phoneNumbers.0.value', '+1 555 0100')
                ->where('address_book.id', $addressBook->id)
                ->where('address_book.display_name', 'People')
                ->missing('card_data')
                ->etc(),
            ),
        );
});

test('contacts page excludes another users contacts', function () {
    $user = User::factory()->create();
    $addressBook = DavAddressBook::factory()->for($user)->create();
    $ownedContact = DavCard::factory()->for($addressBook, 'addressBook')->state(davData([
        'formattedName' => 'Owned Contact',
    ]))->create();

    DavCard::factory()->state(davData([
        'formattedName' => 'Other Contact',
    ]))->create();

    $contacts = $this->actingAs($user)
        ->get('/contacts')
        ->inertiaProps('contacts');

    expect($contacts)->toHaveCount(1)
        ->and($contacts[0]['id'])->toBe($ownedContact->id)
        ->and($contacts[0]['data']['formattedName'])->toBe('Owned Contact');
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
