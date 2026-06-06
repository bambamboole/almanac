<?php

namespace App\Http\Controllers\Calendar;

use App\Http\Resources\CalendarCollection;
use Bambamboole\LaravelDav\Models\DavCalendarInstance;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CalendarController
{
    public function __invoke(Request $request): Response
    {
        $start = now()->subMonths(1)->startOfDay();
        $end = now()->addMonths(3)->endOfDay();
        $user = $request->user();

        $calendars = DavCalendarInstance::query()
            ->where('owner_id', $user->id)
            ->with(['calendar' => fn ($query) => $query
                ->with(['objects' => fn ($query) => $query
                    ->where('component_type', 'VEVENT')
                    ->where('starts_at', '<=', $end)
                    ->where('ends_at', '>=', $start)
                    ->orderBy('starts_at'),
                ]),
            ])
            ->orderBy('display_name')
            ->get();

        return Inertia::render('calendar/index', [
            'calendars' => new CalendarCollection($calendars),
            'window' => $this->windowPayload($start, $end),
        ]);
    }

    /**
     * @return array{start: string, end: string}
     */
    private function windowPayload(CarbonInterface $start, CarbonInterface $end): array
    {
        return [
            'start' => $start->toISOString(),
            'end' => $end->toISOString(),
        ];
    }
}
