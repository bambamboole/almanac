<?php

namespace App\Actions\Contacts;

use Bambamboole\LaravelDav\Exceptions\StaleDavResourceException;
use Bambamboole\LaravelDav\Models\DavCard;
use Bambamboole\LaravelDav\Support\DtoFactory;

class UpdateContactCard
{
    /**
     * @param  array<string, mixed>  $fields  ContactData-shaped attributes.
     *
     * @throws StaleDavResourceException
     */
    public function handle(DavCard $card, array $fields, string $expectedEtag): DavCard
    {
        $card->expectingEtag($expectedEtag);
        $card->data = DtoFactory::contactData($card->data, $fields);
        $card->save();

        return $card->refresh();
    }
}
