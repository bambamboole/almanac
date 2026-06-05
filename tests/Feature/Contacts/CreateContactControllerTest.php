<?php

use App\Models\User;
use Bambamboole\LaravelDav\Models\DavAddressBook;
use Bambamboole\LaravelDav\Models\DavCard;

it('creates a contact for an address book the user owns', function () {
    $user = User::factory()->create();
    $book = DavAddressBook::factory()->for($user)->create();

    $this->actingAs($user)
        ->post('/contacts', [
            'address_book_id' => $book->id,
            'data' => [
                'formattedName' => 'Grace Hopper',
                'givenName' => 'Grace',
                'familyName' => 'Hopper',
                'organization' => 'Compiler Labs',
                'emailAddresses' => [
                    ['label' => 'work', 'value' => 'grace@example.com', 'types' => ['INTERNET', 'WORK'], 'isPreferred' => true],
                ],
                'phoneNumbers' => [
                    ['label' => 'mobile', 'value' => '+1 555 0101', 'types' => ['CELL'], 'isPreferred' => true],
                ],
            ],
        ])
        ->assertRedirect()
        ->assertInertiaFlash('toast.type', 'success')
        ->assertInertiaFlash('toast.message', 'Contact created.');

    $card = DavCard::query()->where('dav_address_book_id', $book->id)->firstOrFail();
    expect($card->data->formattedName)->toBe('Grace Hopper')
        ->and($card->card_data)->toContain('grace@example.com')
        ->and(collect($card->data->emailAddresses)->pluck('value')->all())->toContain('grace@example.com')
        ->and($card->card_data)->toContain('+1 555 0101');
});

it('forbids creating a contact in another user\'s address book', function () {
    $user = User::factory()->create();
    $book = DavAddressBook::factory()->for(User::factory())->create();

    $this->actingAs($user)
        ->post('/contacts', ['address_book_id' => $book->id, 'data' => ['formattedName' => 'X']])
        ->assertSessionHasErrors('address_book_id');
});

it('creates a contact with structured phones and addresses', function () {
    $user = User::factory()->create();
    $book = DavAddressBook::factory()->for($user)->create();

    $this->actingAs($user)
        ->post('/contacts', [
            'address_book_id' => $book->id,
            'data' => [
                'formattedName' => 'Ada Lovelace',
                'emailAddresses' => [
                    ['label' => 'work', 'value' => 'ada@work.example.com', 'types' => ['INTERNET', 'WORK']],
                    ['label' => 'home', 'value' => 'ada@home.example.com', 'types' => ['INTERNET', 'HOME']],
                ],
                'phoneNumbers' => [
                    ['label' => 'mobile', 'value' => '+1 555 0100', 'types' => ['CELL']],
                    ['label' => 'work', 'value' => '+1 555 0101', 'types' => ['WORK']],
                ],
                'addresses' => [
                    [
                        'label' => 'home',
                        'street' => '1 Engine Street',
                        'city' => 'London',
                        'region' => 'London',
                        'postalCode' => 'EC1',
                        'country' => 'United Kingdom',
                        'types' => ['HOME'],
                    ],
                ],
            ],
        ])
        ->assertRedirect();

    $card = DavCard::query()
        ->where('dav_address_book_id', $book->id)
        ->firstOrFail();

    expect($card->data->formattedName)->toBe('Ada Lovelace')
        ->and(collect($card->data->emailAddresses)->pluck('value')->all())->toBe(['ada@work.example.com', 'ada@home.example.com'])
        ->and(collect($card->data->phoneNumbers)->pluck('value')->all())->toBe(['+1 555 0100', '+1 555 0101'])
        ->and($card->data->emailAddresses)->toHaveCount(2)
        ->and($card->data->phoneNumbers)->toHaveCount(2)
        ->and($card->data->addresses)->toHaveCount(1)
        ->and($card->data->addresses[0]->street)->toBe('1 Engine Street')
        ->and($card->card_data)->toContain('ada@work.example.com')
        ->and($card->card_data)->toContain('+1 555 0101')
        ->and($card->card_data)->toContain('1 Engine Street');
});
