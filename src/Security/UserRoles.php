<?php

namespace App\Security;

final class UserRoles
{
    public const ROLE_USER = 'ROLE_USER';
    public const ROLE_SELLER = 'ROLE_SELLER';
    public const ROLE_BUYER = 'ROLE_BUYER';
    public const ROLE_ADMIN = 'ROLE_ADMIN';

    public static function getAllRoles(): array
    {
        return [
            self::ROLE_USER,
            self::ROLE_SELLER,
            self::ROLE_BUYER,
            self::ROLE_ADMIN,
        ];
    }
}