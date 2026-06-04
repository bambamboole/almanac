<?php

namespace App\Enums;

enum Permission: string
{
    case UsersManage = 'users.manage';
    case CollectionsManage = 'collections.manage';
    case DavCredentialsManage = 'dav-credentials.manage';
}
