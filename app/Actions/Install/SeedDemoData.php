<?php

namespace App\Actions\Install;

use App\Models\User;
use Bambamboole\LaravelDav\Models\DavAddressBook;
use Bambamboole\LaravelDav\Models\DavCalendar;
use Bambamboole\LaravelDav\Models\DavCalendarObject;
use Bambamboole\LaravelDav\Models\DavCard;
use Illuminate\Support\Carbon;

class SeedDemoData
{
    public function handle(User $user): void
    {
        $calendar = DavCalendar::factory()
            ->for($user, 'owner')
            ->withInstance([
                'display_name' => 'Personal',
                'uri' => 'personal',
            ])
            ->create();

        foreach ($this->demoEvents(Carbon::today('Europe/Berlin')) as $event) {
            DavCalendarObject::factory()->create([
                'dav_calendar_id' => $calendar->id,
                'uri' => $event['uri'],
                'data' => $event['data'],
            ]);
        }

        $addressBook = DavAddressBook::factory()->create([
            'owner_id' => $user->id,
            'display_name' => 'Personal',
            'uri' => 'personal',
        ]);

        foreach ($this->demoContacts() as $contact) {
            DavCard::factory()->create([
                'dav_address_book_id' => $addressBook->id,
                'uri' => $contact['uri'],
                'data' => $contact['data'],
            ]);
        }
    }

    /**
     * @return array<int, array{uri: string, data: array<string, mixed>}>
     */
    private function demoEvents(Carbon $today): array
    {
        return [
            [
                'uri' => 'demo-weekly-planning.ics',
                'data' => [
                    'uid' => 'demo-weekly-planning',
                    'componentType' => 'VEVENT',
                    'summary' => 'Weekly planning',
                    'description' => 'Review projects, calendar, and next actions.',
                    'location' => 'Home office',
                    'startsAt' => $today->copy()->setTime(9, 0),
                    'endsAt' => $today->copy()->setTime(10, 0),
                    'timezone' => 'Europe/Berlin',
                ],
            ],
            [
                'uri' => 'demo-lunch-with-morgan.ics',
                'data' => [
                    'uid' => 'demo-lunch-with-morgan',
                    'componentType' => 'VEVENT',
                    'summary' => 'Lunch with Morgan',
                    'description' => 'Catch up and talk through the travel plan.',
                    'location' => 'Cafe Central',
                    'startsAt' => $today->copy()->addDay()->setTime(12, 30),
                    'endsAt' => $today->copy()->addDay()->setTime(13, 30),
                    'timezone' => 'Europe/Berlin',
                ],
            ],
            [
                'uri' => 'demo-focus-block.ics',
                'data' => [
                    'uid' => 'demo-focus-block',
                    'componentType' => 'VEVENT',
                    'summary' => 'Focus block',
                    'description' => 'Deep work block for Almanac implementation.',
                    'startsAt' => $today->copy()->addDays(3)->setTime(14, 0),
                    'endsAt' => $today->copy()->addDays(3)->setTime(16, 30),
                    'timezone' => 'Europe/Berlin',
                ],
            ],
            [
                'uri' => 'demo-reset-day.ics',
                'data' => [
                    'uid' => 'demo-reset-day',
                    'componentType' => 'VEVENT',
                    'summary' => 'Reset day',
                    'description' => 'Admin, errands, and personal maintenance.',
                    'startsAt' => $today->copy()->addDays(5)->startOfDay(),
                    'endsAt' => $today->copy()->addDays(6)->startOfDay(),
                    'isAllDay' => true,
                ],
            ],
        ];
    }

    /**
     * @return array<int, array{uri: string, data: array<string, mixed>}>
     */
    private function demoContacts(): array
    {
        return [
            [
                'uri' => 'demo-ada-lovelace.vcf',
                'data' => [
                    'uid' => 'demo-ada-lovelace',
                    'formattedName' => 'Ada Lovelace',
                    'givenName' => 'Ada',
                    'familyName' => 'Lovelace',
                    'organization' => 'Analytical Engines',
                    'jobTitle' => 'Mathematician',
                    'department' => 'Research',
                    'note' => 'Demo contact with Apple-compatible structured fields.',
                    'birthday' => ['year' => 1815, 'month' => 12, 'day' => 10],
                    'pronouns' => [['language' => 'en', 'value' => 'she/her']],
                    'emailAddresses' => [['label' => 'work', 'value' => 'ada@example.com', 'types' => ['INTERNET', 'WORK']]],
                    'phoneNumbers' => [['label' => 'iPhone', 'value' => '+1 555 0100', 'types' => ['CELL'], 'isPreferred' => true]],
                    'addresses' => [[
                        'label' => 'home',
                        'street' => '12 St James Square',
                        'city' => 'London',
                        'postalCode' => 'SW1Y 4LB',
                        'country' => 'United Kingdom',
                        'types' => ['HOME'],
                    ]],
                    'urls' => [['label' => 'home page', 'value' => 'https://ada.example.com']],
                    'instantMessages' => [['label' => 'Jabber', 'service' => 'xmpp', 'username' => 'ada@chat.example.com', 'uri' => 'xmpp:ada@chat.example.com']],
                    'socialProfiles' => [['label' => 'LinkedIn', 'service' => 'linkedin', 'url' => 'https://www.linkedin.com/in/ada-lovelace']],
                    'dates' => [['label' => 'anniversary', 'year' => 1835, 'month' => 7, 'day' => 8]],
                    'relations' => [['label' => 'manager', 'name' => 'Charles Babbage']],
                ],
            ],
            [
                'uri' => 'demo-grace-hopper.vcf',
                'data' => [
                    'uid' => 'demo-grace-hopper',
                    'formattedName' => 'Grace Hopper',
                    'givenName' => 'Grace',
                    'familyName' => 'Hopper',
                    'organization' => 'Compiler Labs',
                    'jobTitle' => 'Computer Scientist',
                    'note' => 'Seeded demo contact for testing CardDAV sync.',
                    'birthday' => ['year' => 1906, 'month' => 12, 'day' => 9],
                    'emailAddresses' => [['label' => 'work', 'value' => 'grace@example.com', 'types' => ['INTERNET', 'WORK']]],
                    'phoneNumbers' => [['label' => 'mobile', 'value' => '+1 555 0101', 'types' => ['CELL']]],
                    'addresses' => [[
                        'label' => 'work',
                        'street' => '1 Compiler Way',
                        'city' => 'Arlington',
                        'region' => 'VA',
                        'postalCode' => '22201',
                        'country' => 'United States',
                        'types' => ['WORK'],
                    ]],
                    'urls' => [['label' => 'home page', 'value' => 'https://grace.example.com']],
                    'instantMessages' => [['label' => 'Jabber', 'service' => 'xmpp', 'username' => 'grace@chat.example.com', 'uri' => 'xmpp:grace@chat.example.com']],
                ],
            ],
            [
                'uri' => 'demo-alan-turing.vcf',
                'data' => [
                    'uid' => 'demo-alan-turing',
                    'formattedName' => 'Alan Turing',
                    'givenName' => 'Alan',
                    'familyName' => 'Turing',
                    'organization' => 'Computation Group',
                    'jobTitle' => 'Researcher',
                    'note' => 'Seeded demo contact with address and URL fields.',
                    'birthday' => ['year' => 1912, 'month' => 6, 'day' => 23],
                    'emailAddresses' => [['label' => 'work', 'value' => 'alan@example.com', 'types' => ['INTERNET', 'WORK']]],
                    'phoneNumbers' => [['label' => 'mobile', 'value' => '+1 555 0102', 'types' => ['CELL']]],
                    'addresses' => [[
                        'label' => 'work',
                        'street' => '23 Computation Lane',
                        'city' => 'Manchester',
                        'postalCode' => 'M1 1AE',
                        'country' => 'United Kingdom',
                        'types' => ['WORK'],
                    ]],
                    'urls' => [['label' => 'home page', 'value' => 'https://alan.example.com']],
                ],
            ],
            [
                'uri' => 'demo-katherine-johnson.vcf',
                'data' => [
                    'uid' => 'demo-katherine-johnson',
                    'formattedName' => 'Katherine Johnson',
                    'givenName' => 'Katherine',
                    'familyName' => 'Johnson',
                    'organization' => 'Navigation Team',
                    'jobTitle' => 'Mathematician',
                    'note' => 'Seeded demo contact with postal address.',
                    'birthday' => ['year' => 1918, 'month' => 8, 'day' => 26],
                    'emailAddresses' => [['label' => 'work', 'value' => 'katherine@example.com', 'types' => ['INTERNET', 'WORK']]],
                    'phoneNumbers' => [['label' => 'mobile', 'value' => '+1 555 0103', 'types' => ['CELL']]],
                    'addresses' => [[
                        'label' => 'work',
                        'street' => '100 Navigation Drive',
                        'city' => 'Hampton',
                        'region' => 'VA',
                        'postalCode' => '23666',
                        'country' => 'United States',
                        'types' => ['WORK'],
                    ]],
                ],
            ],
            [
                'uri' => 'demo-morgan-lee.vcf',
                'data' => [
                    'uid' => 'demo-morgan-lee',
                    'formattedName' => 'Morgan Lee',
                    'givenName' => 'Morgan',
                    'familyName' => 'Lee',
                    'organization' => 'Personal',
                    'note' => 'Seeded personal contact for testing labels.',
                    'birthday' => ['month' => 4, 'day' => 18],
                    'emailAddresses' => [['label' => 'home', 'value' => 'morgan@example.com', 'types' => ['INTERNET', 'HOME']]],
                    'phoneNumbers' => [['label' => 'mobile', 'value' => '+1 555 0104', 'types' => ['CELL']]],
                    'addresses' => [[
                        'label' => 'home',
                        'street' => '44 Personal Street',
                        'city' => 'Berlin',
                        'postalCode' => '10115',
                        'country' => 'Germany',
                        'types' => ['HOME'],
                    ]],
                    'urls' => [['label' => 'home page', 'value' => 'https://morgan.example.com']],
                ],
            ],
        ];
    }
}
