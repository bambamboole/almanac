<?php

namespace App\Http\Controllers\Calendar;

use App\Models\CalendarEvent;
use Bambamboole\LaravelDav\Models\DavCalendar;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CalendarController
{
    public function __invoke(Request $request): Response
    {
        $start = now()->subMonths(1)->startOfDay();
        $end = now()->addMonths(3)->endOfDay();

        $calendars = DavCalendar::query()
            ->whereBelongsTo($request->user())
            ->orderBy('display_name')
            ->get(['id', 'display_name', 'description', 'color', 'timezone', 'components']);

        $events = CalendarEvent::query()
            ->with(['calendar:id,display_name,color'])
            ->whereHas('calendar', fn ($query) => $query->whereBelongsTo($request->user()))
            ->where('component_type', 'VEVENT')
            ->where('starts_at', '<=', $end)
            ->where('ends_at', '>=', $start)
            ->orderBy('starts_at')
            ->get();

        return Inertia::render('calendar/index', [
            'calendars' => $calendars,
            'events' => $events,
            'window' => [
                'start' => $start->toISOString(),
                'end' => $end->toISOString(),
            ],
        ]);
    }
}
