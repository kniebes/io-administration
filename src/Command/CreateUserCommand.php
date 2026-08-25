<?php

declare(strict_types=1);

namespace App\Command;

use Kniebes\IoCore\Entity\User;
use Kniebes\IoCore\Repository\UserRepository;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:user:create',
    description: 'Legt einen neuen Administrations-Benutzer an',
)]
final class CreateUserCommand
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $userPasswordHasher,
    ) {
    }

    public function __invoke(
        SymfonyStyle $symfonyStyle,
        #[Argument(description: 'Benutzername fuer die Anmeldung')]
        string $username,
        #[Argument(description: 'Passwort im Klartext')]
        string $plainPassword,
        #[Option(description: 'Zusaetzlich die Rolle ROLE_ADMIN vergeben')]
        bool $admin = false,
    ): int {
        if ($this->userRepository->findOneByUsername($username) !== null) {
            $symfonyStyle->error('Der Benutzername "' . $username . '" ist bereits vergeben.');

            return Command::FAILURE;
        }

        $user = new User(username: $username, password: '');
        $user->setPassword($this->userPasswordHasher->hashPassword(user: $user, plainPassword: $plainPassword));

        if ($admin) {
            $user->setRoles(['ROLE_ADMIN']);
        }

        $this->userRepository->save($user);

        $symfonyStyle->success('Benutzer "' . $username . '" wurde angelegt.');

        return Command::SUCCESS;
    }
}
