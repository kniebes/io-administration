<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function testUserIdentifierIsTheUsername(): void
    {
        $user = new User(username: 'mk', password: 'gehashtes-passwort');

        self::assertSame('mk', $user->getUserIdentifier());
    }

    public function testEveryUserAlwaysHasRoleUser(): void
    {
        $user = new User(username: 'mk', password: 'gehashtes-passwort');
        $user->roles = ['ROLE_ADMIN'];

        self::assertSame(['ROLE_ADMIN', 'ROLE_USER'], $user->roles);
    }

    public function testNewUserIsActiveByDefault(): void
    {
        $user = new User(username: 'mk', password: 'gehashtes-passwort');

        self::assertTrue($user->isActive);
    }
}
