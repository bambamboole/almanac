<?php

namespace App\Actions\Calendar;

use Bambamboole\LaravelDav\Exceptions\StaleDavResourceException;
use Bambamboole\LaravelDav\Models\DavCalendarObject;
use Bambamboole\LaravelDav\Support\DtoFactory;

class UpdateCalendarObject
{
    /**
     * @param  array<string, mixed>  $data  CalendarObjectData-shaped attributes.
     *
     * @throws StaleDavResourceException
     */
    public function handle(DavCalendarObject $object, array $data, string $expectedEtag): DavCalendarObject
    {
        $object->expectingEtag($expectedEtag);
        $object->data = DtoFactory::calendarObjectData($object->data, $data);
        $object->save();

        return $object->refresh();
    }
}
