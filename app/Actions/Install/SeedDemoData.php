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
        $calendar = DavCalendar::factory()->create([
            'user_id' => $user->id,
            'display_name' => 'Personal',
            'uri' => 'personal',
        ]);

        foreach ($this->demoEvents(Carbon::today('Europe/Berlin')) as $event) {
            DavCalendarObject::factory()->create([
                'dav_calendar_id' => $calendar->id,
                ...$event,
            ]);
        }

        $addressBook = DavAddressBook::factory()->create([
            'user_id' => $user->id,
            'display_name' => 'Personal',
            'uri' => 'personal',
        ]);

        foreach ($this->demoContacts() as $contact) {
            DavCard::factory()->create([
                'dav_address_book_id' => $addressBook->id,
                ...$contact,
            ]);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function demoEvents(Carbon $today): array
    {
        return [
            [
                'uri' => 'demo-weekly-planning.ics',
                'uid' => 'demo-weekly-planning',
                'summary' => 'Weekly planning',
                'description' => 'Review projects, calendar, and next actions.',
                'location' => 'Home office',
                'starts_at' => $today->copy()->setTime(9, 0),
                'ends_at' => $today->copy()->setTime(10, 0),
                'timezone' => 'Europe/Berlin',
            ],
            [
                'uri' => 'demo-lunch-with-morgan.ics',
                'uid' => 'demo-lunch-with-morgan',
                'summary' => 'Lunch with Morgan',
                'description' => 'Catch up and talk through the travel plan.',
                'location' => 'Cafe Central',
                'starts_at' => $today->copy()->addDay()->setTime(12, 30),
                'ends_at' => $today->copy()->addDay()->setTime(13, 30),
                'timezone' => 'Europe/Berlin',
            ],
            [
                'uri' => 'demo-focus-block.ics',
                'uid' => 'demo-focus-block',
                'summary' => 'Focus block',
                'description' => 'Deep work block for Almanac implementation.',
                'starts_at' => $today->copy()->addDays(3)->setTime(14, 0),
                'ends_at' => $today->copy()->addDays(3)->setTime(16, 30),
                'timezone' => 'Europe/Berlin',
            ],
            [
                'uri' => 'demo-reset-day.ics',
                'uid' => 'demo-reset-day',
                'summary' => 'Reset day',
                'description' => 'Admin, errands, and personal maintenance.',
                'starts_at' => $today->copy()->addDays(5)->startOfDay(),
                'ends_at' => $today->copy()->addDays(6)->startOfDay(),
                'is_all_day' => true,
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function demoContacts(): array
    {
        return [
            [
                'uri' => 'demo-ada-lovelace.vcf',
                'uid' => 'demo-ada-lovelace',
                'full_name' => 'Ada Lovelace',
                'given_name' => 'Ada',
                'family_name' => 'Lovelace',
                'organization' => 'Analytical Engines',
                'emails' => ['ada@example.com'],
                'phones' => ['+1 555 0100'],
                'job_title' => 'Mathematician',
                'department' => 'Research',
                'note' => 'Demo contact with Apple-compatible structured fields.',
                'birthday' => ['year' => 1815, 'month' => 12, 'day' => 10],
                'pronouns' => [['language' => 'en', 'value' => 'she/her']],
                'email_addresses' => [['label' => 'work', 'value' => 'ada@example.com', 'types' => ['INTERNET', 'WORK']]],
                'phone_numbers' => [['label' => 'iPhone', 'value' => '+1 555 0100', 'types' => ['CELL'], 'is_preferred' => true]],
                'addresses' => [[
                    'label' => 'home',
                    'street' => '12 St James Square',
                    'city' => 'London',
                    'postal_code' => 'SW1Y 4LB',
                    'country' => 'United Kingdom',
                    'types' => ['HOME'],
                ]],
                'urls' => [['label' => 'home page', 'value' => 'https://ada.example.com']],
                'instant_messages' => [['label' => 'Jabber', 'service' => 'xmpp', 'username' => 'ada@chat.example.com', 'uri' => 'xmpp:ada@chat.example.com']],
                'social_profiles' => [['label' => 'LinkedIn', 'service' => 'linkedin', 'url' => 'https://www.linkedin.com/in/ada-lovelace']],
                'dates' => [['label' => 'anniversary', 'year' => 1835, 'month' => 7, 'day' => 8]],
                'relations' => [['label' => 'manager', 'name' => 'Charles Babbage']],
            ],
            [
                'uri' => 'demo-grace-hopper.vcf',
                'uid' => 'demo-grace-hopper',
                'full_name' => 'Grace Hopper',
                'given_name' => 'Grace',
                'family_name' => 'Hopper',
                'organization' => 'Compiler Labs',
                'emails' => ['grace@example.com'],
                'phones' => ['+1 555 0101'],
                'job_title' => 'Computer Scientist',
                'note' => 'Seeded demo contact for testing CardDAV sync.',
                'birthday' => ['year' => 1906, 'month' => 12, 'day' => 9],
                'email_addresses' => [['label' => 'work', 'value' => 'grace@example.com', 'types' => ['INTERNET', 'WORK']]],
                'phone_numbers' => [['label' => 'mobile', 'value' => '+1 555 0101', 'types' => ['CELL']]],
                'addresses' => [[
                    'label' => 'work',
                    'street' => '1 Compiler Way',
                    'city' => 'Arlington',
                    'region' => 'VA',
                    'postal_code' => '22201',
                    'country' => 'United States',
                    'types' => ['WORK'],
                ]],
                'urls' => [['label' => 'home page', 'value' => 'https://grace.example.com']],
                'instant_messages' => [['label' => 'Jabber', 'service' => 'xmpp', 'username' => 'grace@chat.example.com', 'uri' => 'xmpp:grace@chat.example.com']],
            ],
            [
                'uri' => 'demo-alan-turing.vcf',
                'uid' => 'demo-alan-turing',
                'full_name' => 'Alan Turing',
                'given_name' => 'Alan',
                'family_name' => 'Turing',
                'organization' => 'Computation Group',
                'emails' => ['alan@example.com'],
                'phones' => ['+1 555 0102'],
                'job_title' => 'Researcher',
                'note' => 'Seeded demo contact with address and URL fields.',
                'birthday' => ['year' => 1912, 'month' => 6, 'day' => 23],
                'email_addresses' => [['label' => 'work', 'value' => 'alan@example.com', 'types' => ['INTERNET', 'WORK']]],
                'phone_numbers' => [['label' => 'mobile', 'value' => '+1 555 0102', 'types' => ['CELL']]],
                'addresses' => [[
                    'label' => 'work',
                    'street' => '23 Computation Lane',
                    'city' => 'Manchester',
                    'postal_code' => 'M1 1AE',
                    'country' => 'United Kingdom',
                    'types' => ['WORK'],
                ]],
                'urls' => [['label' => 'home page', 'value' => 'https://alan.example.com']],
            ],
            [
                'uri' => 'demo-katherine-johnson.vcf',
                'uid' => 'demo-katherine-johnson',
                'full_name' => 'Katherine Johnson',
                'given_name' => 'Katherine',
                'family_name' => 'Johnson',
                'organization' => 'Navigation Team',
                'emails' => ['katherine@example.com'],
                'phones' => ['+1 555 0103'],
                'job_title' => 'Mathematician',
                'note' => 'Seeded demo contact with postal address.',
                'birthday' => ['year' => 1918, 'month' => 8, 'day' => 26],
                'email_addresses' => [['label' => 'work', 'value' => 'katherine@example.com', 'types' => ['INTERNET', 'WORK']]],
                'phone_numbers' => [['label' => 'mobile', 'value' => '+1 555 0103', 'types' => ['CELL']]],
                'addresses' => [[
                    'label' => 'work',
                    'street' => '100 Navigation Drive',
                    'city' => 'Hampton',
                    'region' => 'VA',
                    'postal_code' => '23666',
                    'country' => 'United States',
                    'types' => ['WORK'],
                ]],
            ],
            [
                'uri' => 'demo-morgan-lee.vcf',
                'uid' => 'demo-morgan-lee',
                'full_name' => 'Morgan Lee',
                'given_name' => 'Morgan',
                'family_name' => 'Lee',
                'organization' => 'Personal',
                'emails' => ['morgan@example.com'],
                'phones' => ['+1 555 0104'],
                'note' => 'Seeded personal contact for testing labels.',
                'birthday' => ['month' => 4, 'day' => 18],
                'email_addresses' => [['label' => 'home', 'value' => 'morgan@example.com', 'types' => ['INTERNET', 'HOME']]],
                'phone_numbers' => [['label' => 'mobile', 'value' => '+1 555 0104', 'types' => ['CELL']]],
                'addresses' => [[
                    'label' => 'home',
                    'street' => '44 Personal Street',
                    'city' => 'Berlin',
                    'postal_code' => '10115',
                    'country' => 'Germany',
                    'types' => ['HOME'],
                ]],
                'urls' => [['label' => 'home page', 'value' => 'https://morgan.example.com']],
            ],
        ];
    }
}
