<?php declare(strict_types=1);

namespace App\Event;

use App\Entity\BlogPost;
use Symfony\Contracts\EventDispatcher\Event;

final class BlogPostPreSaveEvent extends Event
{
    private bool $isFirstTimePublished = false;

    public function __construct(private readonly BlogPost $blogPost)
    {
    }

    public function getBlogPost(): BlogPost
    {
        return $this->blogPost;
    }

    public function isFirstTimePublished(): bool
    {
        return $this->isFirstTimePublished;
    }

    public function setIsFirstTimePublished(bool $isFirstTimePublished): BlogPostPreSaveEvent
    {
        $this->isFirstTimePublished = $isFirstTimePublished;

        return $this;
    }
}
