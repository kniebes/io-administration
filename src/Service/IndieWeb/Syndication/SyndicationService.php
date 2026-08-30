<?php declare(strict_types=1);

namespace App\Service\IndieWeb\Syndication;

use App\Service\IndieWeb\Syndication\Interface\SyndicationServiceInterface;
use App\Service\IndieWeb\Syndication\Syndicator\Interface\SyndicatorInterface;

readonly class SyndicationService implements SyndicationServiceInterface
{
    /**
     * @param iterable<SyndicatorInterface> $handlers
     */
    public function __construct(
        private iterable $handlers
    )
    {
    }

    public function syndicate(int $blogPostId, int $blogId): void
    {
        foreach ($this->handlers as $handler) {
            $handler->syndicate($blogPostId, $blogId);
        }
    }
}
