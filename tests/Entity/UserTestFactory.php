<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\User;

final class UserTestFactory
{
    public static function create(
        string $name = 'Test User',
        string $email = 'test-user@example.com',
        string $password = 'hash',
        string $accountToken = 'encrypted-token',
    ): User {
        return new User($name, $email, $password, $accountToken);
    }
}
