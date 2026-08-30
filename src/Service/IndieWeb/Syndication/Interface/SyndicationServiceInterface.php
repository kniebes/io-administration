<?php declare(strict_types=1);

namespace App\Service\IndieWeb\Syndication\Interface;

interface SyndicationServiceInterface
{
    public function syndicate(int $blogPostId, int $blogId): void;
}
