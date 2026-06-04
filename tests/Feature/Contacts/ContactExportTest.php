<?php

use App\Models\User;
use Bambamboole\LaravelDav\Models\DavAddressBook;
use Bambamboole\LaravelDav\Models\DavCard;

test('guests cannot export contacts', function () {
    $this->get('/contacts/export')->assertRedirect('/login');
});

test('authenticated user downloads their contacts as a vcf file', function () {
    $user = User::factory()->create();
    $addressBook = DavAddressBook::factory()->for($user)->create();

    DavCard::factory()->for($addressBook, 'addressBook')->create([
        'full_name' => 'Ada Lovelace',
        'given_name' => 'Ada',
        'family_name' => 'Lovelace',
    ]);

    $response = $this->actingAs($user)->get('/contacts/export');

    $response->assertOk();

    expect($response->headers->get('Content-Type'))->toContain('text/vcard');
    expect($response->headers->get('Content-Disposition'))->toContain('attachment')
        ->toContain('contacts.vcf');

    $body = $response->getContent();

    expect($body)->toContain('BEGIN:VCARD')
        ->toContain('END:VCARD')
        ->toContain('Ada Lovelace');
});

test('contacts export concatenates multiple cards', function () {
    $user = User::factory()->create();
    $addressBook = DavAddressBook::factory()->for($user)->create();

    DavCard::factory()->for($addressBook, 'addressBook')->create(['full_name' => 'Grace Hopper']);
    DavCard::factory()->for($addressBook, 'addressBook')->create(['full_name' => 'Alan Turing']);

    $body = $this->actingAs($user)->get('/contacts/export')->getContent();

    expect(substr_count($body, 'BEGIN:VCARD'))->toBe(2);
    expect($body)->toContain('Grace Hopper')->toContain('Alan Turing');
});

test('contacts export only includes the current users cards', function () {
    $user = User::factory()->create();
    $addressBook = DavAddressBook::factory()->for($user)->create();
    DavCard::factory()->for($addressBook, 'addressBook')->create(['full_name' => 'Owned Contact']);

    $otherAddressBook = DavAddressBook::factory()->create();
    DavCard::factory()->for($otherAddressBook, 'addressBook')->create(['full_name' => 'Other Contact']);

    $body = $this->actingAs($user)->get('/contacts/export')->getContent();

    expect($body)->toContain('Owned Contact')
        ->not->toContain('Other Contact');
});

test('contacts export can be scoped to a single address book', function () {
    $user = User::factory()->create();
    $friends = DavAddressBook::factory()->for($user)->create(['display_name' => 'Friends']);
    $work = DavAddressBook::factory()->for($user)->create(['display_name' => 'Work']);
    DavCard::factory()->for($friends, 'addressBook')->create(['full_name' => 'Friend Contact']);
    DavCard::factory()->for($work, 'addressBook')->create(['full_name' => 'Work Contact']);

    $response = $this->actingAs($user)->get("/contacts/address-books/{$friends->id}/export");

    $response->assertOk();

    expect($response->headers->get('Content-Disposition'))->toContain('friends.vcf');

    $body = $response->getContent();

    expect($body)->toContain('Friend Contact')
        ->not->toContain('Work Contact');
});

test('contacts export cannot download another users address book', function () {
    $user = User::factory()->create();
    $otherAddressBook = DavAddressBook::factory()->create();

    $this->actingAs($user)
        ->get("/contacts/address-books/{$otherAddressBook->id}/export")
        ->assertForbidden();
});

test('contacts export can download a single contact vcard', function () {
    $user = User::factory()->create();
    $addressBook = DavAddressBook::factory()->for($user)->create();
    $contact = DavCard::factory()->for($addressBook, 'addressBook')->create([
        'full_name' => 'Single Contact',
    ]);

    $response = $this->actingAs($user)->get("/contacts/{$contact->id}/export");

    $response->assertOk();

    expect($response->headers->get('Content-Type'))->toContain('text/vcard');
    expect($response->headers->get('Content-Disposition'))->toContain('single-contact.vcf');
    expect($response->getContent())->toContain('Single Contact');
});

test('contacts export cannot download another users contact', function () {
    $user = User::factory()->create();
    $otherAddressBook = DavAddressBook::factory()->create();
    $contact = DavCard::factory()->for($otherAddressBook, 'addressBook')->create();

    $this->actingAs($user)
        ->get("/contacts/{$contact->id}/export")
        ->assertForbidden();
});
