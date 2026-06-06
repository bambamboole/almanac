<?php

namespace App\Actions\Calendar;

use Bambamboole\LaravelDav\Exceptions\StaleDavResourceException;
use Bambamboole\LaravelDav\Models\DavCalendarObject;

class DeleteCalendarObject
{
    /**
     * @throws StaleDavResourceException
     */
    public function handle(DavCalendarObject $object, string $expectedEtag): void
    {
        $object->deleteDavResource($expectedEtag);
    }
}
