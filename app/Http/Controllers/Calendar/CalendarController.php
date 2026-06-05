<?php

namespace App\Http\Controllers\Calendar;

use App\Models\CalendarEvent;
use Bambamboole\LaravelDav\Models\DavCalendarInstance;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CalendarController
{
    public function __invoke(Request $request): Response
    {
        $start = now()->subMonths(1)->startOfDay();
        $end = now()->addMonths(3)->endOfDay();

        $calendars = DavCalendarInstance::query()
            ->where('owner_id', $request->user()->id)
            ->with('calendar:id,components')
            ->orderBy('display_name')
            ->get()
            ->map(fn (DavCalendarInstance $instance): array => $this->calendarPayload($instance))
            ->values();

        $events = CalendarEvent::query()
            ->with(['calendar.ownerInstance:id,dav_calendar_id,display_name,color'])
            ->whereHas('calendar', fn ($query) => $query->where('owner_id', $request->user()->id))
            ->where('component_type', 'VEVENT')
            ->where('starts_at', '<=', $end)
            ->where('ends_at', '>=', $start)
            ->orderBy('starts_at')
            ->get()
            ->map(fn (CalendarEvent $event): array => $this->eventPayload($event))
            ->values();

        return Inertia::render('calendar/index', [
            'calendars' => $calendars,
            'events' => $events,
            'window' => [
                'start' => $start->toISOString(),
                'end' => $end->toISOString(),
            ],
        ]);
    }

    /**
     * @return array{id: int, display_name: string, description: string|null, color: string|null, timezone: string|null, components: array<int, string>}
     */
    private function calendarPayload(DavCalendarInstance $instance): array
    {
        return [
            'id' => $instance->dav_calendar_id,
            'display_name' => $instance->display_name,
            'description' => $instance->description,
            'color' => $instance->color,
            'timezone' => $instance->timezone,
            'components' => $instance->calendar->components,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function eventPayload(CalendarEvent $event): array
    {
        $payload = $event->toArray();
        $instance = $event->calendar->ownerInstance;

        $payload['calendar'] = [
            'id' => $event->calendar->id,
            'display_name' => $instance?->display_name ?? 'Calendar',
            'color' => $instance?->color,
        ];

        return $payload;
    }
}
