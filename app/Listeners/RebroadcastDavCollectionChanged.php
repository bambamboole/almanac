<?php

namespace App\Listeners;

use App\Events\DavCollectionChanged as AppDavCollectionChanged;
use Bambamboole\LaravelDav\Events\DavCollectionChanged as PackageDavCollectionChanged;

class RebroadcastDavCollectionChanged
{
    public function handle(PackageDavCollectionChanged $event): void
    {
        AppDavCollectionChanged::dispatch(
            (int) $event->ownerId,
            $event->type,
            $event->collectionId,
            $event->resourceUri,
            $event->operation,
            $event->syncToken,
        );
    }
}
