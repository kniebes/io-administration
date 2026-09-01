<?php declare(strict_types=1);

namespace App\Service\BlogPost\Interface;

use App\Entity\BlogPost;

interface SlugManagerInterface
{
    public function slugChange(string $newSlug, BlogPost $blogpost): void;
}
