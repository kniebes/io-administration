<?php

declare(strict_types=1);

namespace App\Command\Experiments;

use App\Repository\BlogPostRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Serializer\SerializerInterface;

#[AsCommand(name: 'app:experiments:serialize-wrapper', description: 'Hello PhpStorm')]
class SerializeWrapperCommand
{
    public function __construct(
        private readonly BlogPostRepository $blogPostRepository,
        private readonly SerializerInterface $serializer,
    )
    {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        $blogPost = $this->blogPostRepository->find(27447);

        $serializedBlogPost = $this->serializer->serialize($blogPost, 'json', [
            'groups' => ['blog_post:read'],
            'content_width' => '720px'
        ]);
        $data = json_decode($serializedBlogPost, true);

        print_r($data);

        return Command::SUCCESS;
    }
}
