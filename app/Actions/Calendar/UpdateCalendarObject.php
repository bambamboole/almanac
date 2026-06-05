<?php

namespace App\Actions\Calendar;

use App\Actions\Calendar\Concerns\MapsEventFields;
use Bambamboole\LaravelDav\Exceptions\StaleDavResourceException;
use Bambamboole\LaravelDav\Models\DavCalendarObject;
use Bambamboole\LaravelDav\Support\DtoFactory;

class UpdateCalendarObject
{
    use MapsEventFields;

    /**
     * @param  array<string, mixed>  $fields
     *
     * @throws StaleDavResourceException
     */
    public function handle(DavCalendarObject $object, array $fields, string $expectedEtag): DavCalendarObject
    {
        $object->expectingEtag($expectedEtag);
        $object->data = DtoFactory::calendarObjectData($object->data, $this->mapEventFields($fields));
        $object->save();

        return $object->refresh();
    }
}
