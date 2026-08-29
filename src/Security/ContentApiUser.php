<?php declare(strict_types=1);

namespace App\Security;

use Symfony\Component\Security\Core\User\UserInterface;

final class ContentApiUser implements UserInterface
{
    public array $roles {
        get => ['ROLE_API'];
    }

    public function getUserIdentifier(): string
    {
        return 'api';
    }
}
