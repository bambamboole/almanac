<?php

namespace App\Actions\Contacts;

use Bambamboole\LaravelDav\Models\DavAddressBook;
use Bambamboole\LaravelDav\Models\DavCard;
use Bambamboole\LaravelDav\Support\DtoFactory;

class CreateContactCard
{
    /**
     * @param  array<string, mixed>  $fields  ContactData-shaped attributes.
     */
    public function handle(DavAddressBook $addressBook, array $fields): DavCard
    {
        $card = $addressBook->putContact(DtoFactory::contactData($fields));

        return $card->refresh();
    }
}
