<?php

namespace App\Actions\Contacts;

use App\Actions\Concerns\RecordsDavChanges;
use Bambamboole\LaravelDav\Models\DavAddressBook;
use Bambamboole\LaravelDav\Models\DavCard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateContactCard
{
    use RecordsDavChanges;

    /**
     * @param  array<string, mixed>  $fields
     */
    public function handle(DavAddressBook $addressBook, array $fields): DavCard
    {
        return DB::transaction(function () use ($addressBook, $fields): DavCard {
            $uid = (string) Str::uuid();

            $card = DavCard::query()->create([
                'dav_address_book_id' => $addressBook->id,
                'uri' => $uid.'.vcf',
                'uid' => $uid,
                'last_modified_at' => now(),
                ...$fields,
            ]);

            $this->recordAddressBookChange($addressBook, $card->uri, self::OperationAdd);

            return $card->refresh();
        });
    }
}
