<?php

namespace App\Actions\Contacts;

use App\Actions\Concerns\RecordsDavChanges;
use App\Exceptions\StaleEntryException;
use Bambamboole\LaravelDav\Models\DavCard;
use Illuminate\Support\Facades\DB;

class DeleteContactCard
{
    use RecordsDavChanges;

    public function handle(DavCard $card, string $expectedEtag): void
    {
        DB::transaction(function () use ($card, $expectedEtag): void {
            $fresh = DavCard::query()->whereKey($card->getKey())->lockForUpdate()->firstOrFail();

            if ($fresh->etag !== $expectedEtag) {
                throw new StaleEntryException;
            }

            $book = $fresh->addressBook()->firstOrFail();
            $uri = $fresh->uri;
            $fresh->delete();
            $this->recordAddressBookChange($book, $uri, self::OperationDelete);
        });
    }
}
