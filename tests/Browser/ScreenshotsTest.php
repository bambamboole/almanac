<?php

use App\Models\User;
use Bambamboole\LaravelDav\Models\DavAddressBook;
use Bambamboole\LaravelDav\Models\DavCalendarObject;
use Bambamboole\LaravelDav\Models\DavCard;

it('captures dashboard, calendar and contacts screenshots', function () {
    $user = User::factory()->create(['name' => 'Robin Ellery']);

    $personal = davCalendarFor($user, ['display_name' => 'Personal', 'color' => '#4F6043']);
    $work = davCalendarFor($user, ['display_name' => 'Work', 'color' => '#A8843F']);

    $today = today();
    $todayEvents = [
        [$personal, 'Morning planning', 9, 0, 9, 45],
        [$work, 'Design review', 11, 30, 12, 30],
        [$personal, 'Lunch with Sarah', 13, 0, 14, 0],
        [$work, 'Sync retro', 16, 0, 16, 30],
    ];
    foreach ($todayEvents as [$cal, $summary, $sh, $sm, $eh, $em]) {
        DavCalendarObject::factory()->for($cal, 'calendar')->state(davData([
            'componentType' => 'VEVENT',
            'summary' => $summary,
            'startsAt' => $today->copy()->setTime($sh, $sm),
            'endsAt' => $today->copy()->setTime($eh, $em),
            'isAllDay' => false,
        ]))->create();
    }

    $spread = [
        [-9, 'Standup'], [-6, 'Project 1:1'], [-4, 'Workshop'], [-2, 'Release'],
        [3, 'Dentist'], [6, 'Team lunch'], [9, 'Quarterly review'], [12, 'Conference'],
    ];
    foreach ($spread as $i => [$offset, $summary]) {
        $cal = $i % 2 === 0 ? $personal : $work;
        $day = $today->copy()->addDays($offset);
        DavCalendarObject::factory()->for($cal, 'calendar')->state(davData([
            'componentType' => 'VEVENT',
            'summary' => $summary,
            'startsAt' => $day->copy()->setTime(10, 0),
            'endsAt' => $day->copy()->setTime(11, 0),
            'isAllDay' => false,
        ]))->create();
    }

    $book = DavAddressBook::factory()->for($user, 'owner')->create(['display_name' => 'People']);
    $people = [
        ['Ada Lovelace', 'Analytical Engines', 'ada@example.com'],
        ['Alan Turing', 'Bletchley Park', 'alan@example.com'],
        ['Grace Hopper', 'US Navy', 'grace@example.com'],
        ['Katherine Johnson', 'NASA', 'katherine@example.com'],
        ['Edsger Dijkstra', 'TU Eindhoven', 'edsger@example.com'],
        ['Margaret Hamilton', 'MIT', 'margaret@example.com'],
        ['Donald Knuth', 'Stanford', 'donald@example.com'],
        ['Barbara Liskov', 'MIT', 'barbara@example.com'],
    ];
    foreach ($people as [$name, $org, $email]) {
        DavCard::factory()->for($book, 'addressBook')->state(davData([
            'formattedName' => $name,
            'organization' => $org,
            'emailAddresses' => [
                ['label' => 'work', 'value' => $email, 'types' => ['INTERNET', 'WORK']],
            ],
        ]))->create();
    }

    $this->actingAs($user);

    visit('/dashboard')
        ->resize(1440, 900)
        ->assertSee('Robin')
        ->screenshot(true, 'almanac-dashboard');

    visit('/calendar')
        ->resize(1440, 900)
        ->assertSee('Calendar')
        ->assertSee('Morning planning')
        ->screenshot(true, 'almanac-calendar');

    visit('/contacts')
        ->resize(1440, 900)
        ->assertSee('Ada Lovelace')
        ->screenshot(true, 'almanac-contacts');
});
