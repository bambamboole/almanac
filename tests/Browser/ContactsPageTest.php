<?php

use App\Models\User;
use Bambamboole\LaravelDav\Models\DavAddressBook;
use Bambamboole\LaravelDav\Models\DavCard;

it('renders the contacts page without javascript errors', function () {
    $user = User::factory()->create();
    $addressBook = DavAddressBook::factory()->for($user)->create([
        'display_name' => 'People',
    ]);

    DavCard::factory()->for($addressBook, 'addressBook')->create([
        'full_name' => 'Ada Lovelace',
        'organization' => 'Analytical Engines',
        'emails' => ['ada@example.com'],
        'phones' => ['+1 555 0100'],
    ]);

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
