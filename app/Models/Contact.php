<?php

namespace App\Models;

use Bambamboole\LaravelDav\Models\DavCard;
use Illuminate\Database\Eloquent\Attributes\Hidden;

/**
 * Read-side projection of a DAV card: hides the internal payload columns and
 * the verbatim `data.raw` vCard so list/detail responses carry only the typed
 * ContactData the frontend consumes.
 */
#[Hidden(['card_data', 'size'])]
class Contact extends DavCard
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $array = parent::toArray();

        unset($array['data']['raw']);

        return $array;
    }
}
