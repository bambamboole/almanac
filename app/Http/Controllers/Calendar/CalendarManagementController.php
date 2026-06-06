<?php

namespace App\Http\Controllers\Calendar;

use App\Http\Controllers\Controller;
use App\Http\Requests\Calendar\StoreCalendarRequest;
use App\Http\Requests\Calendar\UpdateCalendarRequest;
use Bambamboole\LaravelDav\Models\DavCalendarInstance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CalendarManagementController extends Controller
{
    public function store(StoreCalendarRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $request->user()->createDavCalendar([
            'uri' => Str::slug($data['display_name']) ?: (string) Str::uuid(),
            'display_name' => $data['display_name'],
            'description' => $data['description'] ?? null,
            'color' => $data['color'] ?? null,
            'timezone' => $data['timezone'] ?? null,
            'components' => $this->normalizeComponents($data['components']),
            'sync_token' => 1,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Calendar created.')]);

        return back();
    }

    public function update(UpdateCalendarRequest $request, DavCalendarInstance $calendarInstance): RedirectResponse
    {
        $data = $request->validated();

        $attributes = [
            'display_name' => $data['display_name'],
            'description' => $data['description'] ?? null,
            'color' => $data['color'] ?? null,
            'timezone' => $data['timezone'] ?? null,
        ];

        if ($request->user()->can('updateBackingCalendar', $calendarInstance) && array_key_exists('components', $data)) {
            $attributes['components'] = $this->normalizeComponents($data['components']);
        }

        $calendarInstance->updateDavProperties($attributes);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Calendar updated.')]);

        return back();
    }

    public function destroy(DavCalendarInstance $calendarInstance): RedirectResponse
    {
        $this->authorize('delete', $calendarInstance);

        $message = $calendarInstance->isOwner()
            ? __('Calendar deleted.')
            : __('Calendar removed.');

        $calendarInstance->deleteDavCollection();

        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return back();
    }

    /**
     * @param  array<int, string>  $components
     * @return array<int, string>
     */
    private function normalizeComponents(array $components): array
    {
        return array_values(array_unique(['VEVENT', ...$components]));
    }
}
