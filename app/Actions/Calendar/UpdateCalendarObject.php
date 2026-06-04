<?php

namespace App\Actions\Calendar;

use App\Actions\Concerns\RecordsDavChanges;
use App\Exceptions\StaleEntryException;
use Bambamboole\LaravelDav\Models\DavCalendarObject;
use Bambamboole\LaravelDav\Parsing\CalendarObjectSerializer;
use Illuminate\Support\Facades\DB;

class UpdateCalendarObject
{
    use RecordsDavChanges;

    public function __construct(private CalendarObjectSerializer $serializer) {}

    /**
     * @param  array<string, mixed>  $fields
     *
     * @throws StaleEntryException
     */
    public function handle(DavCalendarObject $object, array $fields, string $expectedEtag): DavCalendarObject
    {
        return DB::transaction(function () use ($object, $fields, $expectedEtag): DavCalendarObject {
            $fresh = DavCalendarObject::query()->whereKey($object->getKey())->lockForUpdate()->firstOrFail();

            if ($fresh->etag !== $expectedEtag) {
                throw new StaleEntryException;
            }

            $existingPayload = $fresh->calendar_data;
            $fresh->fill($fields);
            $payload = $this->serializer->merge($existingPayload, $fresh->toData());

            $fresh->forceFill([
                'calendar_data' => $payload,
                'last_modified_at' => now(),
            ])->save();

            $calendar = $fresh->calendar()->firstOrFail();
            $this->recordCalendarChange($calendar, $fresh->uri, self::OperationModify);

            return $fresh->refresh();
        });
    }
}
