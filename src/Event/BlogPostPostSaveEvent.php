<?php declare(strict_types=1);

namespace App\Event;

use App\Entity\BlogPost;
use Symfony\Contracts\EventDispatcher\Event;

class BlogPostPostSaveEvent extends Event
{
    public function __construct(
        private readonly BlogPost $blogPost,
        private readonly bool $isFirstTimePublished
    )
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

}
