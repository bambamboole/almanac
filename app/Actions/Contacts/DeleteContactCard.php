<?php

namespace App\Actions\Contacts;

use Bambamboole\LaravelDav\Exceptions\StaleDavResourceException;
use Bambamboole\LaravelDav\Models\DavCard;

class DeleteContactCard
{
    /**
     * @throws StaleDavResourceException
     */
    public function handle(DavCard $card, string $expectedEtag): void
    {
        $card->expectingEtag($expectedEtag)->delete();
    }
}
