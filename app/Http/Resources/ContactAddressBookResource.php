<?php

namespace App\Http\Resources;

use Bambamboole\LaravelDav\Models\DavAddressBook;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin DavAddressBook
 */
class ContactAddressBookResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array{id: int, display_name: string, description: string|null, cards_count: int}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'display_name' => $this->display_name,
            'description' => $this->description,
            'cards_count' => (int) $this->cards_count,
        ];
    }
}
