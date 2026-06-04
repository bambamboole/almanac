<?php

namespace App\Actions\Dav;

use App\Models\User;
use Bambamboole\LaravelDav\Models\DavCredential;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateDavCredential
{
    public function __construct(private CreateDefaultDavCollections $createDefaultDavCollections) {}

    /**
     * @return array{credential: DavCredential, username: string, plainSecret: string}
     */
    public function handle(
        User $user,
        string $name,
        ?string $username = null,
        ?string $plainSecret = null,
    ): array {
        $username ??= 'dav-'.$user->getKey().'-'.Str::lower(Str::random(8));
        $plainSecret ??= Str::password(32);

        $this->createDefaultDavCollections->handle($user);

        $credential = DavCredential::query()->updateOrCreate([
            'username' => $username,
        ], [
            'user_id' => $user->getKey(),
            'name' => $name,
            'secret_hash' => Hash::make($plainSecret),
        ]);

        return [
            'credential' => $credential,
            'username' => $username,
            'plainSecret' => $plainSecret,
        ];
    }
}
