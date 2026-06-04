<?php

namespace App\Actions\Calendar;

use App\Actions\Concerns\RecordsDavChanges;
use App\Exceptions\StaleEntryException;
use Bambamboole\LaravelDav\Models\DavCalendarObject;
use Illuminate\Support\Facades\DB;

class DeleteCalendarObject
{
    use RecordsDavChanges;

    public function handle(DavCalendarObject $object, string $expectedEtag): void
    {
        DB::transaction(function () use ($object, $expectedEtag): void {
            $fresh = DavCalendarObject::query()->whereKey($object->getKey())->lockForUpdate()->firstOrFail();

            if ($fresh->etag !== $expectedEtag) {
                throw new StaleEntryException;
            }

            $calendar = $fresh->calendar()->firstOrFail();
            $uri = $fresh->uri;
            $fresh->delete();
            $this->recordCalendarChange($calendar, $uri, self::OperationDelete);
        });
    }
}
