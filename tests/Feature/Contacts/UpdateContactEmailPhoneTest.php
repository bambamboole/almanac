<?php

use App\Models\User;
use Bambamboole\LaravelDav\Models\DavAddressBook;
use Bambamboole\LaravelDav\Models\DavCard;

it('overwrites the primary email/phone while preserving a second email', function () {
    $user = User::factory()->create();
    $book = DavAddressBook::factory()->for($user)->create();

    // Seed a card whose vCard already carries two emails.
    $card = DavCard::factory()->for($book, 'addressBook')->state(davData([
        'formattedName' => 'Alan Turing',
        'emailAddresses' => [['label' => 'work', 'value' => 'alan@old.example.com', 'types' => ['INTERNET']]],
        'phoneNumbers' => [['label' => 'mobile', 'value' => '+1 000', 'types' => ['CELL']]],
    ]))->create([
        'card_data' => "BEGIN:VCARD\r\nVERSION:3.0\r\nUID:c1\r\nFN:Alan Turing\r\nN:Turing;Alan;;;\r\nEMAIL;TYPE=INTERNET:alan@old.example.com\r\nEMAIL;TYPE=INTERNET:alan2@example.com\r\nTEL;TYPE=CELL:+1 000\r\nEND:VCARD\r\n",
    ]);

    $this->actingAs($user)
        ->put("/contacts/{$card->id}", [
            'full_name' => 'Alan Turing',
            'email' => 'alan@new.example.com',
            'phone' => '+1 999',
            'expected_etag' => $card->etag,
        ])
        ->assertRedirect();

    $card->refresh();
    expect($card->card_data)->toContain('alan@new.example.com')
        ->and($card->card_data)->toContain('alan2@example.com')   // second email preserved
        ->and($card->card_data)->not->toContain('alan@old.example.com')
        ->and($card->card_data)->toContain('+1 999');
});
