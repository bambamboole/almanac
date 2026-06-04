<?php

use App\Actions\Contacts\CreateContactCard;
use App\Models\User;
use Bambamboole\LaravelDav\Models\DavAddressBook;
use Bambamboole\LaravelDav\Models\DavChange;

it('creates a contact, serializes a vCard, and records an added change', function () {
    $user = User::factory()->create();
    $book = DavAddressBook::factory()->for($user)->create(['sync_token' => 1]);

    $card = app(CreateContactCard::class)->handle($book, [
        'full_name' => 'Ada Lovelace',
        'given_name' => 'Ada',
        'family_name' => 'Lovelace',
        'organization' => 'Analytical Engines',
        'emails' => ['ada@example.com'],
        'email_addresses' => [['label' => 'work', 'value' => 'ada@example.com', 'types' => ['INTERNET', 'WORK']]],
    ]);

    expect($card->uid)->not->toBeEmpty()
        ->and($card->uri)->toBe($card->uid.'.vcf')
        ->and($card->card_data)->toContain('FN:Ada Lovelace')
        ->and($card->card_data)->toContain('EMAIL')
        ->and($card->card_data)->toContain('ada@example.com');

    expect($book->refresh()->sync_token)->toBe(2);
    expect(DavChange::query()->where('collection_id', $book->id)->where('operation', 1)->where('resource_uri', $card->uri)->exists())->toBeTrue();
});
