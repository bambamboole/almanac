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
        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->startOfWeek()->addWeek();

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

        return Inertia::render('dashboard');
    }
}
