<?php

namespace App\Actions\Contacts;

use App\Actions\Concerns\RecordsDavChanges;
use App\Exceptions\StaleEntryException;
use App\Support\Dav\VCardPayloadMerger;
use Bambamboole\LaravelDav\Models\DavCard;
use Illuminate\Support\Facades\DB;

class UpdateContactCard
{
    use RecordsDavChanges;

    public function __construct(private VCardPayloadMerger $merger) {}

    /**
     * @param  array<string, mixed>  $fields
     *
     * @throws StaleEntryException
     */
    public function handle(DavCard $card, array $fields, string $expectedEtag): DavCard
    {
        return DB::transaction(function () use ($card, $fields, $expectedEtag): DavCard {
            $fresh = DavCard::query()->whereKey($card->getKey())->lockForUpdate()->firstOrFail();

            if ($fresh->etag !== $expectedEtag) {
                throw new StaleEntryException;
            }

            $existingPayload = $fresh->card_data;
            $fresh->fill($fields);
            $payload = $this->merger->merge($existingPayload, $fresh);

            $fresh->forceFill([
                'card_data' => $payload,
                'last_modified_at' => now(),
            ])->save();

            $book = $fresh->addressBook()->firstOrFail();
            $this->recordAddressBookChange($book, $fresh->uri, self::OperationModify);

            return $fresh->refresh();
        });
    }
}
