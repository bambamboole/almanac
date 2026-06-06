<?php

use App\Models\User;
use Bambamboole\LaravelDav\Models\DavAddressBook;
use Bambamboole\LaravelDav\Models\DavCard;

it('edits a contact from the contacts page', function () {
    $user = User::factory()->create();
    $addressBook = DavAddressBook::factory()->for($user, 'owner')->create([
        'display_name' => 'Personal',
    ]);

    DavCard::factory()->for($addressBook, 'addressBook')->state(davData([
        'formattedName' => 'Original Contact Name',
    ]))->create();

    $this->actingAs($user);

    $contact = DavCard::query()
        ->whereHas('addressBook', fn ($q) => $q->where('owner_id', $user->id))
        ->firstOrFail();

    $page = visit('/contacts');

    $page->assertSee('Original Contact Name')
        ->click("[data-contact-row=\"{$contact->id}\"]")
        ->assertVisible('#edit-contact-full-name')
        ->clear('#edit-contact-full-name')
        ->fill('#edit-contact-full-name', 'Updated Contact Name')
        ->click('button[type="submit"]')
        ->assertSee('Updated Contact Name')
        ->assertNoJavaScriptErrors();
});

it('edits a contact email from the UI', function () {
    $user = User::factory()->create();
    $book = DavAddressBook::factory()->for($user, 'owner')->create();
    DavCard::factory()->for($book, 'addressBook')->state(davData([
        'formattedName' => 'Edit Me',
        'emailAddresses' => [
            ['label' => 'work', 'value' => 'before@example.com', 'types' => ['INTERNET', 'WORK']],
        ],
    ]))->create();

    $this->actingAs($user);
    $page = visit('/contacts');

    $page->assertSee('Edit Me')
        ->assertNoJavaScriptErrors()
        ->click('[data-contact-row]')
        ->clear('#edit-contact-email')
        ->fill('#edit-contact-email', 'after@example.com')
        ->click('button[type="submit"]')
        ->assertSee('after@example.com');
});

it('edits structured contact details from the UI', function () {
    $user = User::factory()->create();
    $book = DavAddressBook::factory()->for($user, 'owner')->create();
    $card = DavCard::factory()->for($book, 'addressBook')->state(davData([
        'formattedName' => 'Details Contact',
        'emailAddresses' => [
            ['label' => 'work', 'value' => 'details@example.com', 'types' => ['INTERNET', 'WORK']],
        ],
        'phoneNumbers' => [
            ['label' => 'mobile', 'value' => '+1 555 0100', 'types' => ['CELL']],
        ],
        'addresses' => [
            [
                'label' => 'home',
                'street' => 'Old Street',
                'city' => 'London',
                'types' => ['HOME'],
            ],
        ],
    ]))->create();

    $this->actingAs($user);
    $page = visit('/contacts');

    $page->assertSee('Details Contact')
        ->assertNoJavaScriptErrors()
        ->click("[data-contact-row=\"{$card->id}\"]")
        ->click('Add phone')
        ->fill('#edit-contact-phone-1', '+1 555 0102')
        ->clear('#edit-contact-address-0-street')
        ->fill('#edit-contact-address-0-street', 'New Street')
        ->fill('#edit-contact-address-0-city', 'Cambridge')
        ->click('button[type="submit"]')
        ->assertSee('Details Contact');

    $card->refresh();

    expect(collect($card->data->phoneNumbers)->pluck('value')->all())->toBe(['+1 555 0100', '+1 555 0102'])
        ->and($card->data->addresses)->toHaveCount(1)
        ->and($card->data->addresses[0]->street)->toBe('New Street')
        ->and($card->data->addresses[0]->city)->toBe('Cambridge');
});
