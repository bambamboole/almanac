<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $now = now();
        $startOfToday = $now->copy()->startOfDay();
        $startOfTomorrow = $startOfToday->copy()->addDay();
        $startOfWeek = $now->copy()->startOfWeek();
        $endOfWeek = $startOfWeek->copy()->addWeek();

        $user->load([
            'davCalendarInstances' => fn ($query) => $query
                ->with(['calendar' => fn ($query) => $query
                    ->with(['objects' => fn ($query) => $query
                        ->where('component_type', 'VEVENT')
                        ->where('starts_at', '>=', $startOfWeek)
                        ->where('starts_at', '<', $endOfWeek)
                        ->orderBy('starts_at'),
                    ]),
                ])
                ->orderBy('display_name'),
            'davAddressBooks' => fn ($query) => $query
                ->withCount('cards')
                ->orderBy('display_name'),
        ]);

        $user->loadCount([
            'davCalendars as calendars_count',
            'davCalendarInstances as calendar_instances_count',
            'davAddressBooks as address_books_count',
        ]);

        return Inertia::render('dashboard', [
            'dashboard' => [
                'now' => $now->toISOString(),
                'timezone' => config('app.timezone'),
                'today_start' => $startOfToday->toISOString(),
                'tomorrow_start' => $startOfTomorrow->toISOString(),
                'week_start' => $startOfWeek->toISOString(),
                'next_week_start' => $endOfWeek->toISOString(),
            ],
        ]);
    }
}
