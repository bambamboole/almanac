<?php

namespace App\Actions\Concerns;

use App\Events\DavCollectionChanged;
use Bambamboole\LaravelDav\Models\DavAddressBook;
use Bambamboole\LaravelDav\Models\DavCalendar;
use Bambamboole\LaravelDav\Models\DavChange;
use Illuminate\Support\Facades\DB;

trait RecordsDavChanges
{
    private const OperationAdd = 1;

    private const OperationModify = 2;

    private const OperationDelete = 3;

    private const CalendarCollectionType = 'calendar';

    private const AddressBookCollectionType = 'address_book';

    private function recordCalendarChange(DavCalendar $calendar, ?string $resourceUri, int $operation): void
    {
        $this->recordChange($calendar, self::CalendarCollectionType, $resourceUri, $operation);
    }

    private function recordAddressBookChange(DavAddressBook $addressBook, ?string $resourceUri, int $operation): void
    {
        $this->recordChange($addressBook, self::AddressBookCollectionType, $resourceUri, $operation);
    }

    private function recordChange(DavCalendar|DavAddressBook $collection, string $type, ?string $resourceUri, int $operation): void
    {
        DB::transaction(function () use ($collection, $type, $resourceUri, $operation): void {
            $lockedCollection = $collection->newQuery()
                ->whereKey($collection->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $lockedCollection->forceFill([
                'sync_token' => $lockedCollection->sync_token + 1,
            ])->save();

            DavChange::query()->create([
                'collection_type' => $type,
                'collection_id' => $lockedCollection->getKey(),
                'resource_uri' => $resourceUri,
                'operation' => $operation,
                'sync_token' => $lockedCollection->sync_token,
                'created_at' => now(),
            ]);

            $operationLabel = match ($operation) {
                self::OperationAdd => 'added',
                self::OperationModify => 'modified',
                self::OperationDelete => 'deleted',
                default => 'modified',
            };

            DavCollectionChanged::dispatch(
                (int) $lockedCollection->user_id,
                $type,
                (int) $lockedCollection->getKey(),
                $resourceUri,
                $operationLabel,
                (int) $lockedCollection->sync_token,
            );
        });
    }
}
