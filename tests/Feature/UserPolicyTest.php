<?php

use App\Models\User;

it('allows only admins to manage users', function () {
    $admin = User::factory()->admin()->create();
    $member = User::factory()->create();
    $target = User::factory()->create();

    expect($admin->can('viewAny', User::class))->toBeTrue()
        ->and($admin->can('create', User::class))->toBeTrue()
        ->and($admin->can('update', $target))->toBeTrue()
        ->and($admin->can('delete', $target))->toBeTrue()
        ->and($member->can('viewAny', User::class))->toBeFalse()
        ->and($member->can('update', $target))->toBeFalse();
});

it('forbids an admin from deleting themselves', function () {
    $admin = User::factory()->admin()->create();

    expect($admin->can('delete', $admin))->toBeFalse();
});
