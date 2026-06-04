<?php

use App\Enums\Permission;

it('exposes the admin-elevation permissions as string-backed cases', function () {
    expect(Permission::UsersManage->value)->toBe('users.manage')
        ->and(Permission::CollectionsManage->value)->toBe('collections.manage')
        ->and(Permission::DavCredentialsManage->value)->toBe('dav-credentials.manage')
        ->and(Permission::cases())->toHaveCount(3);
});
