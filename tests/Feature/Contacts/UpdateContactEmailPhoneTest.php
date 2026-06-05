<?php

use App\Models\User;
use Bambamboole\LaravelDav\Models\DavAddressBook;
use Bambamboole\LaravelDav\Models\DavCard;

it('overwrites the email/phone set with structured data', function () {
    $user = User::factory()->create();
    $book = DavAddressBook::factory()->for($user)->create();

    $card = DavCard::factory()->for($book, 'addressBook')->state(davData([
        'formattedName' => 'Alan Turing',
        'emailAddresses' => [['label' => 'work', 'value' => 'alan@old.example.com', 'types' => ['INTERNET']]],
        'phoneNumbers' => [['label' => 'mobile', 'value' => '+1 000', 'types' => ['CELL']]],
    ]))->create();

    $card->refresh();

    $this->actingAs($user)
        ->put("/contacts/{$card->id}", [
            'data' => [
                'formattedName' => 'Alan Turing',
                'emailAddresses' => [
                    ['label' => 'work', 'value' => 'alan@new.example.com', 'types' => ['INTERNET'], 'isPreferred' => true],
                ],
                'phoneNumbers' => [
                    ['label' => 'mobile', 'value' => '+1 999', 'types' => ['CELL'], 'isPreferred' => true],
                ],
            ],
            'expected_etag' => $card->etag,
        ])
        ->assertRedirect();

    $card->refresh();
    expect($card->card_data)->toContain('alan@new.example.com')
        ->and($card->card_data)->not->toContain('alan@old.example.com')
        ->and($card->card_data)->toContain('+1 999');
});
