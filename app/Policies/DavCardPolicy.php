<?php

namespace App\Policies;

use App\Models\User;
use Bambamboole\LaravelDav\Models\DavCard;

class DavCardPolicy
{
    public function view(User $user, DavCard $card): bool
    {
        return $card->addressBook->owner_id === $user->id;
    }

    public function update(User $user, DavCard $card): bool
    {
        return $card->addressBook->owner_id === $user->id;
    }

    public function delete(User $user, DavCard $card): bool
    {
        return $card->addressBook->owner_id === $user->id;
    }
}
