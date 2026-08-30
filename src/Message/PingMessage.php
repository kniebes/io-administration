<?php declare(strict_types=1);

namespace App\Message;

readonly class PingMessage
{
    public function __construct(private int $blogId, private int $blogPostId)
    {
    }

    public function getBlogId(): int
    {
        return $this->blogId;
    }

    public function getBlogPostId(): int
    {
        return $this->blogPostId;
    }


}
