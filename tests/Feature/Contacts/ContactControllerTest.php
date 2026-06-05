<?php

use App\Models\User;
use Bambamboole\LaravelDav\Models\DavAddressBook;
use Bambamboole\LaravelDav\Models\DavCard;

beforeEach(function () {
    $this->withoutVite();
});

test('owner can update a contact full_name', function () {
    $user = User::factory()->create();
    $addressBook = DavAddressBook::factory()->for($user)->create();
    $contact = DavCard::factory()->for($addressBook, 'addressBook')->state(davData([
        'formattedName' => 'Original Name',
    ]))->create();

    $this->actingAs($user)
        ->putJson("/contacts/{$contact->id}", [
            'full_name' => 'Updated Name',
            'expected_etag' => $contact->etag,
        ])
        ->assertRedirect()
        ->assertInertiaFlash('toast.type', 'success')
        ->assertInertiaFlash('toast.message', 'Contact updated.');

    expect($contact->fresh()->data->formattedName)->toBe('Updated Name');
});

test('stale etag returns 409', function () {
    $user = User::factory()->create();
    $addressBook = DavAddressBook::factory()->for($user)->create();
    $contact = DavCard::factory()->for($addressBook, 'addressBook')->state(davData([
        'formattedName' => 'Original Name',
    ]))->create();

    $this->actingAs($user)
        ->putJson("/contacts/{$contact->id}", [
            'full_name' => 'Updated Name',
            'expected_etag' => 'not-the-real-etag',
        ])
        ->assertStatus(409);
});

test('note is preserved when editing only the name', function () {
    $user = User::factory()->create();
    $addressBook = DavAddressBook::factory()->for($user)->create();
    $contact = DavCard::factory()->for($addressBook, 'addressBook')->state(davData([
        'formattedName' => 'Original Name',
        'note' => 'keep this note',
    ]))->create(['card_data' => null]);

    // card_data is auto-generated on save; re-fetch to get the generated etag
    $contact->refresh();

    $this->actingAs($user)
        ->putJson("/contacts/{$contact->id}", [
            'full_name' => 'New',
            'note' => 'keep this note',
            'expected_etag' => $contact->etag,
        ])
        ->assertRedirect();

    $contact->refresh();
    expect($contact->card_data)->toContain('NOTE:keep this note');
    expect($contact->card_data)->toContain('FN:New');
});

test('contacts page payload includes note for each contact', function () {
    $user = User::factory()->create();
    $addressBook = DavAddressBook::factory()->for($user)->create();
    DavCard::factory()->for($addressBook, 'addressBook')->state(davData([
        'formattedName' => 'Test Person',
        'note' => 'my important note',
    ]))->create();

    $this->actingAs($user)
        ->get('/contacts')
        ->assertInertia(fn ($page) => $page
            ->component('contacts/index')
            ->has('contacts', 1, fn ($contact) => $contact
                ->where('note', 'my important note')
                ->etc()
            )
        );
});

test('contacts page payload includes structured contact fields', function () {
    $user = User::factory()->create();
    $addressBook = DavAddressBook::factory()->for($user)->create();
    DavCard::factory()->for($addressBook, 'addressBook')->state(davData([
        'formattedName' => 'Structured Person',
        'emailAddresses' => [
            ['label' => 'work', 'value' => 'structured@example.com', 'types' => ['INTERNET', 'WORK']],
        ],
        'phoneNumbers' => [
            ['label' => 'mobile', 'value' => '+1 555 0100', 'types' => ['CELL']],
        ],
        'addresses' => [
            [
                'label' => 'home',
                'street' => '1 Payload Street',
                'city' => 'London',
                'postalCode' => 'EC1',
                'country' => 'United Kingdom',
                'types' => ['HOME'],
            ],
        ],
    ]))->create();

    $this->actingAs($user)
        ->get('/contacts')
        ->assertInertia(fn ($page) => $page
            ->component('contacts/index')
            ->has('contacts', 1, fn ($contact) => $contact
                ->where('email_addresses.0.value', 'structured@example.com')
                ->where('phone_numbers.0.value', '+1 555 0100')
                ->where('addresses.0.street', '1 Payload Street')
                ->etc()
            )
        );
});

test('cannot update another users contact', function () {
    $owner = User::factory()->create();
    $addressBook = DavAddressBook::factory()->for($owner)->create();
    $contact = DavCard::factory()->for($addressBook, 'addressBook')->state(davData([
        'formattedName' => 'Original Name',
    ]))->create();

    $otherUser = User::factory()->create();

    $this->actingAs($otherUser)
        ->putJson("/contacts/{$contact->id}", [
            'full_name' => 'Hacked Name',
            'expected_etag' => $contact->etag,
        ])
        ->assertForbidden();
});
