<?php

use App\Models\User;
use Bambamboole\LaravelDav\Models\DavCalendar;
use Bambamboole\LaravelDav\Models\DavCalendarObject;

it('groups all-day and timed events on the same day together', function () {
    $user = User::factory()->create();
    $calendar = DavCalendar::factory()->for($user)->create([
        'display_name' => 'Personal',
    ]);
    $eventDate = today();

    DavCalendarObject::factory()->for($calendar, 'calendar')->create([
        'summary' => 'All-day planning',
        'starts_at' => $eventDate->copy()->startOfDay(),
        'ends_at' => $eventDate->copy()->addDay()->startOfDay(),
        'is_all_day' => true,
    ]);

    DavCalendarObject::factory()->for($calendar, 'calendar')->create([
        'summary' => 'Morning focus',
        'starts_at' => $eventDate->copy()->setTime(9, 0),
        'ends_at' => $eventDate->copy()->setTime(10, 0),
        'is_all_day' => false,
    ]);

    $this->actingAs($user);

    $page = visit('/calendar');

    $page->assertSee('Calendar')
        ->assertSee('Organize your events and appointments.')
        ->assertSee('All-day planning')
        ->assertSee('Morning focus')
        ->assertScript(
            "document.querySelectorAll('[data-calendar-day-group]').length",
            1,
        )
        ->assertScript(
            "[...document.querySelectorAll('[data-calendar-day-group]')].filter((section) => section.textContent.includes('All-day planning') && section.textContent.includes('Morning focus')).length",
            1,
        )
        ->assertScript(
            "(() => {
                const button = document.querySelector('.fc-dayGridMonth-button.fc-button-active');
                const probe = document.createElement('span');
                probe.style.backgroundColor = getComputedStyle(document.documentElement).getPropertyValue('--secondary').trim();
                document.body.appendChild(probe);
                const secondary = getComputedStyle(probe).backgroundColor;
                const styles = getComputedStyle(button);
                probe.remove();

                return styles.backgroundColor === secondary && styles.color !== secondary;
            })()",
            true,
        )
        ->assertScript(
            "(() => {
                const height = document.querySelector('[data-calendar-view]').getBoundingClientRect().height;

                return height >= 570 && height <= 737;
            })()",
            true,
        )
        ->assertNoJavaScriptErrors();
});
