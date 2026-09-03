<?php declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PasswordStrength;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
    name: 'app:user:create',
    description: 'Creates a new administration user',
)]
final class CreateUserCommand
{
    private const int MINIMUM_PASSWORD_LENGTH = 12;

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $userPasswordHasher,
        private readonly ValidatorInterface $validator,
    ) {
    }

    public function __invoke(
        SymfonyStyle $symfonyStyle,
        InputInterface $input,
        #[Argument(description: 'Username used to sign in')]
        string $username,
        #[Argument(description: 'Plain text password; better left out and entered hidden')]
        ?string $plainPassword = null,
        #[Option(description: 'Also grant the role ROLE_ADMIN')]
        bool $admin = false,
        #[Option(description: 'Accept the password even if it breaks the rules')]
        bool $forcePassword = false,
    ): int {
        if ($this->userRepository->findOneByUsername($username) !== null) {
            $symfonyStyle->error('The username "' . $username . '" is already taken.');

            return Command::FAILURE;
        }

        if ($plainPassword === null) {
            if (!$input->isInteractive()) {
                $symfonyStyle->error('Without interaction the password has to be passed as an argument.');

                return Command::INVALID;
            }

            $plainPassword = $this->askForPassword($symfonyStyle);

            if ($plainPassword === null) {
                return Command::FAILURE;
            }
        }

        $violationMessages = $this->collectPasswordViolations($plainPassword);

        if ($violationMessages !== [] && !$forcePassword) {
            $symfonyStyle->error('The password does not meet the requirements:');
            $symfonyStyle->listing($violationMessages);

            return Command::FAILURE;
        }

        $user = new User(username: $username, password: '');
        $user->setPassword($this->userPasswordHasher->hashPassword(user: $user, plainPassword: $plainPassword));

        if ($admin) {
            $user->setRoles(['ROLE_ADMIN']);
        }

        $this->userRepository->save($user);

        $symfonyStyle->success('User "' . $username . '" has been created.');

        return Command::SUCCESS;
    }

    private function askForPassword(SymfonyStyle $symfonyStyle): ?string
    {
        $plainPassword = (string) $symfonyStyle->askHidden('Password');
        $repeatedPassword = (string) $symfonyStyle->askHidden('Repeat password');

        if ($plainPassword !== $repeatedPassword) {
            $symfonyStyle->error('The two entries do not match.');

            return null;
        }

        return $plainPassword;
    }

    /**
     * @return list<string>
     */
    private function collectPasswordViolations(string $plainPassword): array
    {
        $violations = $this->validator->validate(
            value: $plainPassword,
            constraints: [
                new NotBlank(),
                new Length(min: self::MINIMUM_PASSWORD_LENGTH),
                new PasswordStrength(minScore: PasswordStrength::STRENGTH_STRONG),
            ],
        );

        $violationMessages = [];

        foreach ($violations as $violation) {
            $violationMessages[] = (string) $violation->getMessage();
        }

        return $violationMessages;
    }
}
