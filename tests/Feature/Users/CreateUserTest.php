<?php

use App\Actions\Users\CreateUser;
use App\Enums\Permission;
use App\Enums\UserRole;
use Bambamboole\LaravelDav\Models\DavAddressBook;
use Bambamboole\LaravelDav\Models\DavCalendar;
use Bambamboole\LaravelDav\Models\DavCalendarInstance;
use Illuminate\Support\Facades\Hash;

it('creates a verified admin user', function () {
    $user = app(CreateUser::class)->handle(
        name: 'Admin',
        email: 'admin@example.com',
        password: 'secret-password',
        role: UserRole::Admin,
        emailVerified: true,
        createDefaultDavCollections: false,
    );

    expect($user->name)->toBe('Admin')
        ->and($user->email)->toBe('admin@example.com')
        ->and($user->email_verified_at)->not->toBeNull()
        ->and(Hash::check('secret-password', $user->password))->toBeTrue()
        ->and($user->role->name)->toBe(UserRole::Admin->value)
        ->and($user->hasPermission(Permission::cases()[0]))->toBeTrue()
        ->and(DavCalendar::query()->where('owner_id', $user->id)->exists())->toBeFalse()
        ->and(DavAddressBook::query()->where('owner_id', $user->id)->exists())->toBeFalse();
});

it('creates a member user with default dav collections by default', function () {
    $user = app(CreateUser::class)->handle(
        name: 'Member',
        email: 'member@example.com',
        password: 'secret-password',
    );

    expect($user->role->name)->toBe(UserRole::Member->value)
        ->and($user->email_verified_at)->toBeNull()
        ->and(DavCalendarInstance::query()->where('owner_id', $user->id)->where('uri', 'personal')->exists())->toBeTrue()
        ->and(DavAddressBook::query()->where('owner_id', $user->id)->where('uri', 'personal')->exists())->toBeTrue();
});
