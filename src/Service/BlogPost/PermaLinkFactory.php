<?php declare(strict_types=1);

namespace App\Service\BlogPost;

use App\Entity\Blog;
use App\Entity\BlogPost;

class PermaLinkFactory
{
    public function create(Blog $blog, array $blogPostData): string
    {
        $pattern = $blog->getPermaLinkPattern();
        $replacements = [
            '%id%' => $blogPostData['id'],
            '%slug%' => $blogPostData['slug'],
            '%year%' => (string) date('Y', $blogPostData['publishedDate']->getTimestamp()),
            '%month%' => (string) date('m', $blogPostData['publishedDate']->getTimestamp()),
            '%day%' => (string)date('d', $blogPostData['publishedDate']->getTimestamp()),
        ];

        return $blog->getBaseUrl().strtr($pattern, $replacements);
    }
}
