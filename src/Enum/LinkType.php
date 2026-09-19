<?php declare(strict_types=1);

namespace App\Enum;

enum LinkType: string
{
    case Bookmark = 'bookmark';
    case BlogPostLink = 'blog_post_link';
}

