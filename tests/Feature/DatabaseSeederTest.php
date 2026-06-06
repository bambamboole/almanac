<?php

use App\Models\User;
use Bambamboole\LaravelDav\Models\DavAddressBook;
use Bambamboole\LaravelDav\Models\DavCalendarInstance;
use Bambamboole\LaravelDav\Models\DavCalendarObject;
use Bambamboole\LaravelDav\Models\DavCard;

test('database seeder creates rerunnable demo life data', function () {
    $this->seed();
    $this->seed();

    $user = User::query()->where('email', 'demo@example.com')->firstOrFail();
    $calendarInstance = DavCalendarInstance::query()
        ->where('owner_id', $user->id)
        ->where('uri', 'personal')
        ->firstOrFail();
    $addressBook = DavAddressBook::query()
        ->where('owner_id', $user->id)
        ->where('uri', 'personal')
        ->firstOrFail();

    expect(User::query()->where('email', 'demo@example.com')->count())->toBe(1)
        ->and($calendarInstance->display_name)->toBe('Personal')
        ->and($calendarInstance->calendar->objects()->count())->toBe(4)
        ->and($calendarInstance->calendar->objects()->where('is_all_day', true)->count())->toBe(1)
        ->and($addressBook->display_name)->toBe('Personal')
        ->and($addressBook->cards()->count())->toBe(5)
        ->and(DavCalendarObject::query()->where('uri', 'demo-weekly-planning.ics')->count())->toBe(1)
        ->and(DavCalendarObject::query()->where('uri', 'demo-weekly-planning.ics')->firstOrFail()->data->summary)->toBe('Weekly planning')
        ->and(DavCard::query()->where('uri', 'demo-ada-lovelace.vcf')->count())->toBe(1);

    $ada = DavCard::query()->where('uri', 'demo-ada-lovelace.vcf')->firstOrFail();

    expect($ada->data->uid)->toBe('demo-ada-lovelace')
        ->and($ada->data->formattedName)->toBe('Ada Lovelace')
        ->and($ada->data->phoneNumbers)->not->toBeEmpty()
        ->and($ada->data->emailAddresses)->not->toBeEmpty()
        ->and($ada->data->addresses)->not->toBeEmpty()
        ->and($ada->data->urls)->not->toBeEmpty()
        ->and($ada->data->instantMessages)->not->toBeEmpty()
        ->and($ada->data->birthday)->not->toBeNull()
        ->and($ada->data->note)->not->toBeNull()
        ->and($ada->card_data)->toContain('ADR')
        ->and($ada->card_data)->toContain('URL')
        ->and($ada->card_data)->toContain('IMPP')
        ->and($ada->card_data)->toContain('BDAY');
});

test('database seeder creates a working fixed dav credential', function () {
    $this->seed();

    $this->call('PROPFIND', '/dav/', server: davBasicAuth('demo', 'password'))
        ->assertStatus(207);
});
