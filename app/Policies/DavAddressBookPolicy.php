<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;
use Bambamboole\LaravelDav\Models\DavAddressBook;

class DavAddressBookPolicy
{
    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, DavAddressBook $addressBook): bool
    {
        return $this->update($user, $addressBook);
    }

    public function update(User $user, DavAddressBook $addressBook): bool
    {
        return $addressBook->user_id === $user->id
            || $user->hasPermission(Permission::CollectionsManage);
    }

    public function delete(User $user, DavAddressBook $addressBook): bool
    {
        return $this->update($user, $addressBook);
    }
}
