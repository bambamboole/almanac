<?php

namespace App\Actions\Calendar;

use Bambamboole\LaravelDav\Models\DavCalendar;
use Bambamboole\LaravelDav\Models\DavCalendarObject;
use Bambamboole\LaravelDav\Support\DtoFactory;

class CreateCalendarObject
{
    /**
     * @param  array<string, mixed>  $data  CalendarObjectData-shaped attributes.
     */
    public function handle(DavCalendar $calendar, array $data): DavCalendarObject
    {
        $calendar->loadMissing('ownerInstance');

        $object = $calendar->putObject(DtoFactory::calendarObjectData([
            'componentType' => 'VEVENT',
            'timezone' => $calendar->ownerInstance?->timezone,
            ...$data,
        ]));

        return $object->refresh();
    }
}
