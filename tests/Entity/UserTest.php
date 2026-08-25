<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use Kniebes\IoCore\Entity\User;
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
        $user->setRoles(['ROLE_ADMIN']);

        self::assertSame(['ROLE_ADMIN', 'ROLE_USER'], $user->getRoles());
    }

    public function testNewUserIsActiveByDefault(): void
    {
        $user = new User(username: 'mk', password: 'gehashtes-passwort');

        self::assertTrue($user->isActive());
    }
}
