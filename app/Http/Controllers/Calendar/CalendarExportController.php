<?php

namespace App\Http\Controllers\Calendar;

use App\Http\Controllers\Controller;
use Bambamboole\LaravelDav\Models\DavCalendar;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Sabre\VObject\Component;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Reader;

class CalendarExportController extends Controller
{
    /**
     * Stream the current user's calendars merged into a single .ics download.
     */
    public function __invoke(Request $request): Response
    {
        $calendars = $request->user()->calendars()->with('objects')->get();

        return $this->responseFor($calendars->all(), 'calendar.ics');
    }

    public function show(Request $request, DavCalendar $calendar): Response
    {
        $this->authorize('view', $calendar);

        return $this->responseFor([$calendar->load('objects')], Str::slug($calendar->display_name).'.ics');
    }

    /**
     * @param  array<int, DavCalendar>  $calendars
     */
    private function responseFor(array $calendars, string $filename): Response
    {
        $master = new VCalendar;

        /** @var array<string, true> $seenTimezones */
        $seenTimezones = [];

        foreach ($calendars as $calendar) {
            foreach ($calendar->objects as $object) {
                $this->mergeObject($master, $object->calendar_data, $seenTimezones);
            }
        }

        return response($master->serialize(), 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * Copy the calendar components of a single iCalendar payload into the master
     * calendar, de-duplicating VTIMEZONE components by their TZID.
     *
     * @param  array<string, true>  $seenTimezones
     */
    private function mergeObject(VCalendar $master, string $calendarData, array &$seenTimezones): void
    {
        $vcalendar = Reader::read($calendarData);

        foreach ($vcalendar->getComponents() as $component) {
            if (! $component instanceof Component) {
                continue;
            }

            if ($component->name === 'VTIMEZONE') {
                $tzid = (string) ($component->{'TZID'} ?? '');

                if ($tzid !== '' && isset($seenTimezones[$tzid])) {
                    continue;
                }

                if ($tzid !== '') {
                    $seenTimezones[$tzid] = true;
                }
            }

            $master->add(clone $component);
        }
    }
}
