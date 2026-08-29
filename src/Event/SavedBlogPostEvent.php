<?php declare(strict_types=1);

namespace App\Event;

use App\Entity\Blog;
use App\Entity\BlogPost;
use Symfony\Contracts\EventDispatcher\Event;

final class SavedBlogPostEvent extends Event
{
    public function __construct(private BlogPost $blogPost)
    {
    }

    public function getBlogPost(): BlogPost
    {
        return $this->blogPost;
    }
}
