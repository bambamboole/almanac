<?php

use App\Actions\Users\UpdateUser;
use App\Enums\UserRole;
use App\Models\User;

it('updates a user profile and role', function () {
    $user = User::factory()->create();

    $updated = app(UpdateUser::class)->handle(
        user: $user,
        name: 'Updated User',
        email: 'updated@example.com',
        role: UserRole::Admin,
    );

    expect($updated->name)->toBe('Updated User')
        ->and($updated->email)->toBe('updated@example.com')
        ->and($updated->role->name)->toBe(UserRole::Admin->value);
});
