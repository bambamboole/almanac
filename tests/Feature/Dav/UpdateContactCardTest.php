<?php

use App\Actions\Contacts\UpdateContactCard;
use App\Exceptions\StaleEntryException;
use Bambamboole\LaravelDav\Models\DavAddressBook;
use Bambamboole\LaravelDav\Models\DavCard;
use Bambamboole\LaravelDav\Models\DavChange;

it('updates modeled fields, regenerates payload, bumps etag, and records a change', function () {
    $book = DavAddressBook::factory()->create();
    $card = DavCard::factory()->for($book, 'addressBook')->create(['full_name' => 'Old Name']);
    $originalEtag = $card->etag;
    $originalToken = $book->sync_token;

    $updated = app(UpdateContactCard::class)->handle($card, ['full_name' => 'New Name'], $originalEtag);

    expect($updated->full_name)->toBe('New Name')
        ->and($updated->etag)->not->toBe($originalEtag)
        ->and($updated->card_data)->toContain('FN:New Name')
        ->and($book->refresh()->sync_token)->toBe($originalToken + 1)
        ->and(DavChange::query()->where('collection_id', $book->id)->where('operation', 2)->exists())->toBeTrue();
});

it('rejects a stale etag', function () {
    $card = DavCard::factory()->create();

    expect(fn () => app(UpdateContactCard::class)->handle($card, ['full_name' => 'X'], 'not-the-etag'))
        ->toThrow(StaleEntryException::class);
});

it('preserves unmodeled vCard content when updating', function () {
    $book = DavAddressBook::factory()->create();
    $raw = "BEGIN:VCARD\r\nVERSION:3.0\r\nUID:keep\r\nFN:Old Name\r\nN:Old;Name;;;\r\nEMAIL;TYPE=INTERNET:a@example.com\r\nPHOTO;VALUE=uri:https://example.com/p.jpg\r\nX-CUSTOM:keepme\r\nEND:VCARD\r\n";
    $card = DavCard::factory()->for($book, 'addressBook')->create([
        'uid' => 'keep',
        'full_name' => 'Old Name',
        'emails' => ['a@example.com'],
        'card_data' => $raw,
    ]);

    $updated = app(UpdateContactCard::class)->handle($card, ['full_name' => 'New Name'], $card->etag);

    expect($updated->card_data)->toContain('FN:New Name')
        ->and($updated->card_data)->toContain('EMAIL')
        ->and($updated->card_data)->toContain('a@example.com')
        ->and($updated->card_data)->toContain('PHOTO')
        ->and($updated->card_data)->toContain('https://example.com/p.jpg')
        ->and($updated->card_data)->toContain('X-CUSTOM:keepme');
});
