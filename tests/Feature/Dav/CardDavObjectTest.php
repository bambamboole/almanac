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

    expect(DavCard::query()->where('uri', 'contact-1.vcf')->first())
        ->not->toBeNull()
        ->uid->toBe('contact-1')
        ->full_name->toBe('Ada Lovelace')
        ->given_name->toBe('Ada')
        ->family_name->toBe('Lovelace')
        ->organization->toBe('Analytical Engines')
        ->emails->toBe(['ada@example.com'])
        ->phones->toBe(['+491234567'])
        ->card_data->toBe($payload);

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
            ->and($card->full_name)->toBe('Ada Byron')
            ->and($card->given_name)->toBe('Ada')
            ->and($card->family_name)->toBe('Byron')
            ->and($card->organization)->toBe('Analytical Engines')
            ->and($card->emails)->toBe(['ada@example.com', 'ada@analytical.example'])
            ->and($card->phones)->toBe(['+491234567'])
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

    expect($card->contact_type)->toBe('organization')
        ->and($card->middle_name)->toBe('Augusta')
        ->and($card->name_prefix)->toBe('Countess')
        ->and($card->nickname)->toBe('Enchantress of Numbers')
        ->and($card->department)->toBe('Research')
        ->and($card->job_title)->toBe('Mathematician')
        ->and($card->note)->toBe('First programmer')
        ->and($card->birthday)->toBeInstanceOf(ContactDate::class)
        ->and($card->birthday->year)->toBe(1815)
        ->and($card->birthday->month)->toBe(12)
        ->and($card->birthday->day)->toBe(10);

    expect($card->pronouns)->toHaveCount(1)
        ->and($card->pronouns->first())->toBeInstanceOf(ContactPronoun::class)
        ->and($card->pronouns->first()->language)->toBe('en')
        ->and($card->pronouns->first()->value)->toBe('she/her');

    expect($card->phone_numbers)->toHaveCount(1)
        ->and($card->phone_numbers->first())->toBeInstanceOf(ContactPhoneNumber::class)
        ->and($card->phone_numbers->first()->label)->toBe('iPhone')
        ->and($card->phone_numbers->first()->value)->toBe('+1 (555) 010-0')
        ->and($card->phone_numbers->first()->isPreferred)->toBeTrue();

    expect($card->email_addresses)->toHaveCount(1)
        ->and($card->email_addresses->first())->toBeInstanceOf(ContactEmailAddress::class)
        ->and($card->email_addresses->first()->label)->toBe('Personal')
        ->and($card->email_addresses->first()->value)->toBe('ada@example.com');

    expect($card->addresses)->toHaveCount(1)
        ->and($card->addresses->first())->toBeInstanceOf(ContactPostalAddress::class)
        ->and($card->addresses->first()->label)->toBe('Billing')
        ->and($card->addresses->first()->street)->toBe('12 St James Square')
        ->and($card->addresses->first()->city)->toBe('London')
        ->and($card->addresses->first()->postalCode)->toBe('SW1Y 4LB')
        ->and($card->addresses->first()->country)->toBe('United Kingdom');

    expect($card->urls)->toHaveCount(1)
        ->and($card->urls->first())->toBeInstanceOf(ContactUrl::class)
        ->and($card->urls->first()->label)->toBe('home page')
        ->and($card->urls->first()->value)->toBe('https://ada.example.com');

    expect($card->instant_messages)->toHaveCount(1)
        ->and($card->instant_messages->first())->toBeInstanceOf(ContactInstantMessage::class)
        ->and($card->instant_messages->first()->label)->toBe('Jabber')
        ->and($card->instant_messages->first()->service)->toBe('xmpp')
        ->and($card->instant_messages->first()->username)->toBe('ada@jabber.example.com');

    expect($card->social_profiles)->toHaveCount(1)
        ->and($card->social_profiles->first())->toBeInstanceOf(ContactSocialProfile::class)
        ->and($card->social_profiles->first()->label)->toBe('LinkedIn')
        ->and($card->social_profiles->first()->service)->toBe('linkedin')
        ->and($card->social_profiles->first()->url)->toBe('https://www.linkedin.com/in/ada');

    expect($card->relations)->toHaveCount(1)
        ->and($card->relations->first())->toBeInstanceOf(ContactRelation::class)
        ->and($card->relations->first()->label)->toBe('manager')
        ->and($card->relations->first()->name)->toBe('Charles Babbage');

    expect($card->dates)->toHaveCount(1)
        ->and($card->dates->first()->label)->toBe('anniversary')
        ->and($card->dates->first()->year)->toBe(1835)
        ->and($card->dates->first()->month)->toBe(7)
        ->and($card->dates->first()->day)->toBe(8);

    expect($card->vcard_extensions)->toHaveCount(1)
        ->and($card->vcard_extensions->first())->toBeInstanceOf(ContactVCardExtension::class)
        ->and($card->vcard_extensions->first()->name)->toBe('X-ALMANAC-CUSTOM')
        ->and($card->vcard_extensions->first()->value)->toBe('preserve-me');
});

it('serializes typed contact value objects into carddav vcard fields', function () {
    $user = User::factory()->create();
    $credential = app(CreateDavCredential::class)->handle($user, 'Phone');
    $addressBook = DavAddressBook::query()->whereBelongsTo($user, 'user')->where('uri', 'personal')->firstOrFail();
    $authHeader = cardDavAuthHeader($credential['username'], $credential['plainSecret']);
    $card = DavCard::factory()->for($addressBook, 'addressBook')->create([
        'uri' => 'typed-contact.vcf',
        'uid' => 'typed-contact',
        'full_name' => 'Ada Lovelace',
        'given_name' => 'Ada',
        'middle_name' => 'Augusta',
        'family_name' => 'Lovelace',
        'name_prefix' => 'Countess',
        'organization' => 'Analytical Engines',
        'department' => 'Research',
        'job_title' => 'Mathematician',
        'contact_type' => 'organization',
        'nickname' => 'Enchantress of Numbers',
        'note' => 'First programmer',
        'birthday' => ['year' => 1815, 'month' => 12, 'day' => 10],
        'pronouns' => [
            ['language' => 'en', 'value' => 'she/her'],
        ],
        'phone_numbers' => [
            ['label' => 'iPhone', 'value' => '+1 (555) 010-0', 'types' => ['CELL'], 'is_preferred' => true],
        ],
        'email_addresses' => [
            ['label' => 'Personal', 'value' => 'ada@example.com', 'types' => ['INTERNET', 'HOME']],
        ],
        'addresses' => [
            ['label' => 'Billing', 'street' => '12 St James Square', 'city' => 'London', 'postal_code' => 'SW1Y 4LB', 'country' => 'United Kingdom', 'types' => ['HOME']],
        ],
        'urls' => [
            ['label' => 'home page', 'value' => 'https://ada.example.com'],
        ],
        'instant_messages' => [
            ['label' => 'Jabber', 'service' => 'xmpp', 'username' => 'ada@jabber.example.com', 'uri' => 'xmpp:ada@jabber.example.com'],
        ],
        'social_profiles' => [
            ['label' => 'LinkedIn', 'service' => 'linkedin', 'url' => 'https://www.linkedin.com/in/ada'],
        ],
        'relations' => [
            ['label' => 'manager', 'name' => 'Charles Babbage'],
        ],
        'dates' => [
            ['label' => 'anniversary', 'year' => 1835, 'month' => 7, 'day' => 8],
        ],
        'vcard_extensions' => [
            ['name' => 'X-ALMANAC-CUSTOM', 'value' => 'preserve-me', 'parameters' => []],
        ],
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
    $addressBook = DavAddressBook::query()->whereBelongsTo($user, 'user')->where('uri', 'personal')->firstOrFail();
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
    $ownerAddressBook = DavAddressBook::query()->whereBelongsTo($owner, 'user')->where('uri', 'personal')->firstOrFail();
    $existingCard = DavCard::factory()->for($ownerAddressBook, 'addressBook')->create([
        'uri' => 'contact-1.vcf',
        'full_name' => 'Private',
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
        ->and($existingCard->refresh()->full_name)->toBe('Private')
        ->and(DavCard::query()->where('full_name', 'Overwrite')->exists())->toBeFalse();
})->with(['GET', 'PUT', 'DELETE']);
