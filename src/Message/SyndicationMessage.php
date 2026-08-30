<?php declare(strict_types=1);

namespace App\Message;

class SyndicationMessage
{
    public function __construct(private readonly int $blogPostId)
    {
    }

    public function getBlogPostId(): int
    {
        return $this->blogPostId;
    }
}
