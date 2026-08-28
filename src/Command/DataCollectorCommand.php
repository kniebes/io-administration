<?php

declare(strict_types=1);

namespace App\Command;

use App\Model\DataCollector\BlogPostRequestData;
use App\Service\DataCollector\Interface\DataCollectorServiceInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:data_collector', description: 'Hello PhpStorm')]
class DataCollectorCommand
{
    public function __construct(
        private readonly DataCollectorServiceInterface $dataCollectorService,
    )
    {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        $requestData = new BlogPostRequestData(id: 4);

        $result = $this->dataCollectorService->collect($requestData);

        print_r($result);

        return Command::SUCCESS;
    }
}
