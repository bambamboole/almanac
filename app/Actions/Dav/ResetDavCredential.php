<?php

namespace App\Actions\Dav;

use App\Models\User;
use Bambamboole\LaravelDav\Models\DavCredential;

class ResetDavCredential
{
    public function __construct(private readonly CreateDavCredential $createDavCredential) {}

    /**
     * @return array{username: string, plainSecret: string}
     */
    public function handle(User $user, string $name): array
    {
        DavCredential::query()->where('owner_id', $user->id)->delete();

        return $this->createDavCredential->handle($user, $name);
    }
}
