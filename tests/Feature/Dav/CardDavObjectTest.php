<?php

use App\Actions\Dav\CreateDavCredential;
use App\Models\User;
use Bambamboole\LaravelDav\Dto\Contact\ContactDate;
use Bambamboole\LaravelDav\Dto\Contact\ContactEmailAddress;
use Bambamboole\LaravelDav\Dto\Contact\ContactInstantMessage;
use Bambamboole\LaravelDav\Dto\Contact\ContactPhoneNumber;
use Bambamboole\LaravelDav\Dto\Contact\ContactPostalAddress;
use Bambamboole\LaravelDav\Dto\Contact\ContactPronoun;
use Bambamboole\LaravelDav\Dto\Contact\ContactRelation;
use Bambamboole\LaravelDav\Dto\Contact\ContactSocialProfile;
use Bambamboole\LaravelDav\Dto\Contact\ContactUrl;
use Bambamboole\LaravelDav\Dto\Contact\ContactVCardExtension;
use Bambamboole\LaravelDav\Models\DavAddressBook;
use Bambamboole\LaravelDav\Models\DavCard;
use Illuminate\Support\Carbon;

it('stores fetches and deletes contact cards through carddav', function () {
    $user = User::factory()->create();
    $credential = app(CreateDavCredential::class)->handle($user, 'Phone');
    $payload = "BEGIN:VCARD\r\nVERSION:3.0\r\nPRODID:-//Almanac//Tests//EN\r\nUID:contact-1\r\nFN:Ada Lovelace\r\nN:Lovelace;Ada;;;\r\nEMAIL;TYPE=work:ada@example.com\r\nTEL;TYPE=cell:+491234567\r\nORG:Analytical Engines\r\nEND:VCARD\r\n";
    $path = '/dav/addressbooks/'.$user->id.'/personal/contact-1.vcf';
    $authHeader = cardDavAuthHeader($credential['username'], $credential['plainSecret']);

    cardDavPut($this, $path, $authHeader, $payload)->assertSuccessful();

    $card = DavCard::query()->where('uri', 'contact-1.vcf')->first();

    expect($card)
        ->not->toBeNull()
        ->card_data->toBe($payload)
        ->and($card->data->uid)->toBe('contact-1')
        ->and($card->data->formattedName)->toBe('Ada Lovelace')
        ->and($card->data->givenName)->toBe('Ada')
        ->and($card->data->familyName)->toBe('Lovelace')
        ->and($card->data->organization)->toBe('Analytical Engines')
        ->and(collect($card->data->emailAddresses)->pluck('value')->all())->toBe(['ada@example.com'])
        ->and(collect($card->data->phoneNumbers)->pluck('value')->all())->toBe(['+491234567']);

    $this->withHeaders(['Authorization' => $authHeader])
        ->get($path)
        ->assertSuccessful()
        ->assertContent($payload);

    $this->withHeaders(['Authorization' => $authHeader])
        ->delete($path)
        ->assertSuccessful();

    $this->withHeaders(['Authorization' => $authHeader])
        ->get($path)
        ->assertNotFound();
});

it('updates existing contact cards through carddav', function () {
    try {
        Carbon::setTestNow('2026-06-03 09:00:00');

        $user = User::factory()->create();
        $credential = app(CreateDavCredential::class)->handle($user, 'Phone');
        $path = '/dav/addressbooks/'.$user->id.'/personal/contact-1.vcf';
        $authHeader = cardDavAuthHeader($credential['username'], $credential['plainSecret']);
        $originalPayload = cardDavPayload([
            'UID' => 'contact-1',
            'FN' => 'Ada Lovelace',
            'N' => ['value' => ['Lovelace', 'Ada', '', '', '']],
            'EMAIL' => ['value' => 'ada@example.com', 'parameters' => ['TYPE' => 'work']],
        ]);
        $updatedPayload = cardDavPayload([
            'UID' => 'contact-1',
            'FN' => 'Ada Byron',
            'N' => ['value' => ['Byron', 'Ada', '', '', '']],
            'EMAIL' => [
                ['value' => 'ada@example.com', 'parameters' => ['TYPE' => 'work']],
                ['value' => 'ada@analytical.example', 'parameters' => ['TYPE' => 'home']],
            ],
            'TEL' => ['value' => '+491234567', 'parameters' => ['TYPE' => 'cell']],
            'ORG' => 'Analytical Engines',
        ]);

        cardDavPut($this, $path, $authHeader, $originalPayload)->assertSuccessful();

        $card = DavCard::query()->where('uri', 'contact-1.vcf')->firstOrFail();
        $originalSize = $card->size;
        $originalLastModifiedAt = $card->last_modified_at;

        Carbon::setTestNow('2026-06-03 10:00:00');

        cardDavPut($this, $path, $authHeader, $updatedPayload)->assertSuccessful();

        $card->refresh();

        expect(DavCard::query()->where('uri', 'contact-1.vcf')->count())->toBe(1)
            ->and($card->data->formattedName)->toBe('Ada Byron')
            ->and($card->data->givenName)->toBe('Ada')
            ->and($card->data->familyName)->toBe('Byron')
            ->and($card->data->organization)->toBe('Analytical Engines')
            ->and(collect($card->data->emailAddresses)->pluck('value')->all())->toBe(['ada@example.com', 'ada@analytical.example'])
            ->and(collect($card->data->phoneNumbers)->pluck('value')->all())->toBe(['+491234567'])
            ->and($card->etag)->toBe(sha1($updatedPayload))
            ->and($card->etag)->not->toBe(sha1($originalPayload))
            ->and($card->size)->toBe(strlen($updatedPayload))
            ->and($card->size)->not->toBe($originalSize)
            ->and($card->last_modified_at?->toIso8601String())->toBe('2026-06-03T10:00:00+00:00')
            ->and($card->last_modified_at?->eq($originalLastModifiedAt))->toBeFalse()
            ->and($card->card_data)->toBe($updatedPayload);
    } finally {
        Carbon::setTestNow();
    }
});

it('maps apple contact fields into typed eloquent value objects', function () {
    $user = User::factory()->create();
    $credential = app(CreateDavCredential::class)->handle($user, 'Phone');
    $path = '/dav/addressbooks/'.$user->id.'/personal/contact-apple.vcf';
    $authHeader = cardDavAuthHeader($credential['username'], $credential['plainSecret']);
    $payload = implode("\r\n", [
        'BEGIN:VCARD',
        'VERSION:3.0',
        'PRODID:-//Almanac//Tests//EN',
        'UID:contact-apple',
        'FN:Ada Lovelace',
        'N:Lovelace;Ada;Augusta;Countess;',
        'NICKNAME:Enchantress of Numbers',
        'ORG:Analytical Engines;Research',
        'TITLE:Mathematician',
        'PRONOUNS;LANGUAGE=en:she/her',
        'item1.TEL;TYPE=CELL;TYPE=pref:+1 (555) 010-0',
        'item1.X-ABLABEL:iPhone',
        'item2.EMAIL;TYPE=INTERNET;TYPE=HOME:ada@example.com',
        'item2.X-ABLABEL:Personal',
        'item3.ADR;TYPE=HOME:;;12 St James Square;London;;SW1Y 4LB;United Kingdom',
        'item3.X-ABLABEL:Billing',
        'item4.URL:https://ada.example.com',
        'item4.X-ABLABEL:_$!<HomePage>!$_',
        'item5.IMPP:xmpp:ada@jabber.example.com',
        'item5.X-ABLABEL:Jabber',
        'item6.X-SOCIALPROFILE;TYPE=linkedin:https://www.linkedin.com/in/ada',
        'item6.X-ABLABEL:LinkedIn',
        'item7.X-ABRELATEDNAMES:Charles Babbage',
        'item7.X-ABLABEL:manager',
        'BDAY:1815-12-10',
        'item8.X-ABDATE:1835-07-08',
        'item8.X-ABLABEL:anniversary',
        'NOTE:First programmer',
        'X-ABShowAs:COMPANY',
        'X-ALMANAC-CUSTOM:preserve-me',
        'END:VCARD',
        '',
    ]);

    cardDavPut($this, $path, $authHeader, $payload)->assertSuccessful();

    $card = DavCard::query()->where('uri', 'contact-apple.vcf')->firstOrFail();

    expect($card->data->contactType)->toBe('organization')
        ->and($card->data->middleName)->toBe('Augusta')
        ->and($card->data->namePrefix)->toBe('Countess')
        ->and($card->data->nickname)->toBe('Enchantress of Numbers')
        ->and($card->data->department)->toBe('Research')
        ->and($card->data->jobTitle)->toBe('Mathematician')
        ->and($card->data->note)->toBe('First programmer')
        ->and($card->data->birthday)->toBeInstanceOf(ContactDate::class)
        ->and($card->data->birthday->year)->toBe(1815)
        ->and($card->data->birthday->month)->toBe(12)
        ->and($card->data->birthday->day)->toBe(10);

    expect($card->data->pronouns)->toHaveCount(1)
        ->and($card->data->pronouns[0])->toBeInstanceOf(ContactPronoun::class)
        ->and($card->data->pronouns[0]->language)->toBe('en')
        ->and($card->data->pronouns[0]->value)->toBe('she/her');

    expect($card->data->phoneNumbers)->toHaveCount(1)
        ->and($card->data->phoneNumbers[0])->toBeInstanceOf(ContactPhoneNumber::class)
        ->and($card->data->phoneNumbers[0]->label)->toBe('iPhone')
        ->and($card->data->phoneNumbers[0]->value)->toBe('+1 (555) 010-0')
        ->and($card->data->phoneNumbers[0]->isPreferred)->toBeTrue();

    expect($card->data->emailAddresses)->toHaveCount(1)
        ->and($card->data->emailAddresses[0])->toBeInstanceOf(ContactEmailAddress::class)
        ->and($card->data->emailAddresses[0]->label)->toBe('Personal')
        ->and($card->data->emailAddresses[0]->value)->toBe('ada@example.com');

    expect($card->data->addresses)->toHaveCount(1)
        ->and($card->data->addresses[0])->toBeInstanceOf(ContactPostalAddress::class)
        ->and($card->data->addresses[0]->label)->toBe('Billing')
        ->and($card->data->addresses[0]->street)->toBe('12 St James Square')
        ->and($card->data->addresses[0]->city)->toBe('London')
        ->and($card->data->addresses[0]->postalCode)->toBe('SW1Y 4LB')
        ->and($card->data->addresses[0]->country)->toBe('United Kingdom');

    expect($card->data->urls)->toHaveCount(1)
        ->and($card->data->urls[0])->toBeInstanceOf(ContactUrl::class)
        ->and($card->data->urls[0]->label)->toBe('home page')
        ->and($card->data->urls[0]->value)->toBe('https://ada.example.com');

    expect($card->data->instantMessages)->toHaveCount(1)
        ->and($card->data->instantMessages[0])->toBeInstanceOf(ContactInstantMessage::class)
        ->and($card->data->instantMessages[0]->label)->toBe('Jabber')
        ->and($card->data->instantMessages[0]->service)->toBe('xmpp')
        ->and($card->data->instantMessages[0]->username)->toBe('ada@jabber.example.com');

    expect($card->data->socialProfiles)->toHaveCount(1)
        ->and($card->data->socialProfiles[0])->toBeInstanceOf(ContactSocialProfile::class)
        ->and($card->data->socialProfiles[0]->label)->toBe('LinkedIn')
        ->and($card->data->socialProfiles[0]->service)->toBe('linkedin')
        ->and($card->data->socialProfiles[0]->url)->toBe('https://www.linkedin.com/in/ada');

    expect($card->data->relations)->toHaveCount(1)
        ->and($card->data->relations[0])->toBeInstanceOf(ContactRelation::class)
        ->and($card->data->relations[0]->label)->toBe('manager')
        ->and($card->data->relations[0]->name)->toBe('Charles Babbage');

    expect($card->data->dates)->toHaveCount(1)
        ->and($card->data->dates[0]->label)->toBe('anniversary')
        ->and($card->data->dates[0]->year)->toBe(1835)
        ->and($card->data->dates[0]->month)->toBe(7)
        ->and($card->data->dates[0]->day)->toBe(8);

    expect($card->data->extensions)->toHaveCount(1)
        ->and($card->data->extensions[0])->toBeInstanceOf(ContactVCardExtension::class)
        ->and($card->data->extensions[0]->name)->toBe('X-ALMANAC-CUSTOM')
        ->and($card->data->extensions[0]->value)->toBe('preserve-me');
});

it('serializes typed contact value objects into carddav vcard fields', function () {
    $user = User::factory()->create();
    $credential = app(CreateDavCredential::class)->handle($user, 'Phone');
    $addressBook = DavAddressBook::query()->whereBelongsTo($user, 'owner')->where('uri', 'personal')->firstOrFail();
    $authHeader = cardDavAuthHeader($credential['username'], $credential['plainSecret']);
    $card = DavCard::factory()->for($addressBook, 'addressBook')->state(davData([
        'uid' => 'typed-contact',
        'formattedName' => 'Ada Lovelace',
        'givenName' => 'Ada',
        'middleName' => 'Augusta',
        'familyName' => 'Lovelace',
        'namePrefix' => 'Countess',
        'organization' => 'Analytical Engines',
        'department' => 'Research',
        'jobTitle' => 'Mathematician',
        'contactType' => 'organization',
        'nickname' => 'Enchantress of Numbers',
        'note' => 'First programmer',
        'birthday' => ['year' => 1815, 'month' => 12, 'day' => 10],
        'pronouns' => [
            ['language' => 'en', 'value' => 'she/her'],
        ],
        'phoneNumbers' => [
            ['label' => 'iPhone', 'value' => '+1 (555) 010-0', 'types' => ['CELL'], 'isPreferred' => true],
        ],
        'emailAddresses' => [
            ['label' => 'Personal', 'value' => 'ada@example.com', 'types' => ['INTERNET', 'HOME']],
        ],
        'addresses' => [
            ['label' => 'Billing', 'street' => '12 St James Square', 'city' => 'London', 'postalCode' => 'SW1Y 4LB', 'country' => 'United Kingdom', 'types' => ['HOME']],
        ],
        'urls' => [
            ['label' => 'home page', 'value' => 'https://ada.example.com'],
        ],
        'instantMessages' => [
            ['label' => 'Jabber', 'service' => 'xmpp', 'username' => 'ada@jabber.example.com', 'uri' => 'xmpp:ada@jabber.example.com'],
        ],
        'socialProfiles' => [
            ['label' => 'LinkedIn', 'service' => 'linkedin', 'url' => 'https://www.linkedin.com/in/ada'],
        ],
        'relations' => [
            ['label' => 'manager', 'name' => 'Charles Babbage'],
        ],
        'dates' => [
            ['label' => 'anniversary', 'year' => 1835, 'month' => 7, 'day' => 8],
        ],
        'extensions' => [
            ['name' => 'X-ALMANAC-CUSTOM', 'value' => 'preserve-me', 'parameters' => []],
        ],
    ]))->create([
        'uri' => 'typed-contact.vcf',
    ]);

    $payload = $this->withHeaders(['Authorization' => $authHeader])
        ->get('/dav/addressbooks/'.$user->id.'/personal/'.$card->uri)
        ->assertSuccessful()
        ->getContent();

    expect($payload)->toContain('X-ABSHOWAS:COMPANY')
        ->toContain('N:Lovelace;Ada;Augusta;Countess;')
        ->toContain('ORG:Analytical Engines;Research')
        ->toContain('TITLE:Mathematician')
        ->toContain('NICKNAME:Enchantress of Numbers')
        ->toContain('BDAY:1815-12-10')
        ->toContain('PRONOUNS;LANGUAGE=en:she/her')
        ->toContain('NOTE:First programmer')
        ->toContain('TEL')
        ->toContain('X-ABLABEL:iPhone')
        ->toContain('EMAIL')
        ->toContain('X-ABLABEL:Personal')
        ->toContain('ADR')
        ->toContain('X-ABLABEL:Billing')
        ->toContain('https://ada.example.com')
        ->toContain('IMPP:xmpp:ada@jabber.example.com')
        ->toContain('X-SOCIALPROFILE')
        ->toContain('X-ABRELATEDNAMES:Charles Babbage')
        ->toContain('X-ABDATE:1835-07-08')
        ->toContain('X-ALMANAC-CUSTOM:preserve-me');
});

it('increments the address book change token after card mutations', function () {
    $user = User::factory()->create();
    $credential = app(CreateDavCredential::class)->handle($user, 'Phone');
    $addressBook = DavAddressBook::query()->whereBelongsTo($user, 'owner')->where('uri', 'personal')->firstOrFail();
    $path = '/dav/addressbooks/'.$user->id.'/personal/contact-1.vcf';
    $authHeader = cardDavAuthHeader($credential['username'], $credential['plainSecret']);
    $originalPayload = cardDavPayload([
        'UID' => 'contact-1',
        'FN' => 'Ada Lovelace',
        'N' => ['value' => ['Lovelace', 'Ada', '', '', '']],
    ]);
    $updatedPayload = cardDavPayload([
        'UID' => 'contact-1',
        'FN' => 'Ada Byron',
        'N' => ['value' => ['Byron', 'Ada', '', '', '']],
    ]);

    expect($addressBook->sync_token)->toBe(1);

    cardDavPut($this, $path, $authHeader, $originalPayload)->assertSuccessful();
    expect($addressBook->refresh()->sync_token)->toBe(2);

    cardDavPut($this, $path, $authHeader, $updatedPayload)->assertSuccessful();
    expect($addressBook->refresh()->sync_token)->toBe(3);

    $this->withHeaders(['Authorization' => $authHeader])
        ->delete($path)
        ->assertSuccessful();

    expect($addressBook->refresh()->sync_token)->toBe(4);
});

it('does not allow a dav credential to access another users contact cards', function (string $method) {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();
    app(CreateDavCredential::class)->handle($owner, 'Laptop');
    $attackerCredential = app(CreateDavCredential::class)->handle($attacker, 'Phone');
    $ownerAddressBook = DavAddressBook::query()->whereBelongsTo($owner, 'owner')->where('uri', 'personal')->firstOrFail();
    $existingCard = DavCard::factory()->for($ownerAddressBook, 'addressBook')->state(davData([
        'formattedName' => 'Private',
    ]))->create([
        'uri' => 'contact-1.vcf',
    ]);
    $path = '/dav/addressbooks/'.$owner->id.'/personal/contact-1.vcf';
    $authHeader = cardDavAuthHeader($attackerCredential['username'], $attackerCredential['plainSecret']);
    $payload = cardDavPayload([
        'UID' => 'contact-1',
        'FN' => 'Overwrite',
        'N' => ['value' => ['Overwrite', '', '', '', '']],
    ]);

    $response = match ($method) {
        'GET' => $this->withHeaders(['Authorization' => $authHeader])->get($path),
        'PUT' => cardDavPut($this, $path, $authHeader, $payload),
        'DELETE' => $this->withHeaders(['Authorization' => $authHeader])->delete($path),
    };

    expect($response->getStatusCode())->toBeIn([403, 404])
        ->and(DavCard::query()->where('dav_address_book_id', $ownerAddressBook->id)->count())->toBe(1)
        ->and($existingCard->refresh()->data->formattedName)->toBe('Private')
        ->and(DavCard::query()->get()->contains(fn (DavCard $card): bool => $card->data->formattedName === 'Overwrite'))->toBeFalse();
})->with(['GET', 'PUT', 'DELETE']);
