<?php

use App\Actions\Dav\ResetDavCredential;
use App\Models\User;
use Bambamboole\LaravelDav\Models\DavCredential;

it('replaces a user\'s dav credentials with a fresh one', function () {
    $user = User::factory()->create();
    DavCredential::factory()->for($user)->count(2)->create();

    $result = app(ResetDavCredential::class)->handle($user, 'Reset by admin');

    expect($result)->toHaveKeys(['username', 'plainSecret'])
        ->and(DavCredential::query()->where('user_id', $user->id)->count())->toBe(1);
});
