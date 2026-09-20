<?php

declare(strict_types=1);

namespace App\Command\Maintenance;

use App\Repository\LinkRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:maintenance:remove-orphaned-links', description: 'Remove orphaned Links')]
class RemoveOrphanedLinksCommand
{
    public function __construct(
        private readonly LinkRepository $linkRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Option(description: 'force removal')] bool $force = false
    ): int {
        $io->title('Removing orphaned links');

        $orphanedLinks = $this->linkRepository->findOrphaned();
        $io->writeln('Number of orphaned links found: '.count($orphanedLinks));

        if (count($orphanedLinks) === 0) {
            $io->writeln('stopping...');
            return Command::SUCCESS;
        }

        foreach ($orphanedLinks as $link) {
            $message = sprintf('Identified orphaned link: "%s"', $link->getUrl());
            if ($force) {
                $message = sprintf('Removing orphaned link "%s".', $link->getUrl());
                $this->entityManager->remove($link);
            }
            $io->writeln($message);
        }

        if ($force) {
            try {
                $this->entityManager->flush();
            } catch (\Exception $e) {
                $io->error($e->getMessage());

                return Command::FAILURE;
            }
        }

        return Command::SUCCESS;
    }
}
