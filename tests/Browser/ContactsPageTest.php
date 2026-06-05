<?php

use App\Models\User;
use Bambamboole\LaravelDav\Models\DavAddressBook;
use Bambamboole\LaravelDav\Models\DavCard;

it('renders the contacts page without javascript errors', function () {
    $user = User::factory()->create();
    $addressBook = DavAddressBook::factory()->for($user, 'owner')->create([
        'display_name' => 'People',
    ]);

    DavCard::factory()->for($addressBook, 'addressBook')->state(davData([
        'formattedName' => 'Ada Lovelace',
        'organization' => 'Analytical Engines',
        'emailAddresses' => [
            ['label' => 'work', 'value' => 'ada@example.com', 'types' => ['INTERNET', 'WORK']],
        ],
        'phoneNumbers' => [
            ['label' => 'mobile', 'value' => '+1 555 0100', 'types' => ['CELL'], 'isPreferred' => true],
        ],
    ]))->create();

    $this->actingAs($user);

    $page = visit('/contacts');

    $page->assertSee('Contacts')
        ->assertSee('Organize your people and address books.')
        ->assertSee('People')
        ->assertSee('Ada Lovelace')
        ->assertSee('Analytical Engines')
        ->assertSee('ada@example.com')
        ->assertNoJavaScriptErrors();
});
