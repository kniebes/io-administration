<?php declare(strict_types=1);

namespace App\Model\Filter;

use App\Entity\Blog;
use App\Entity\BlogPostType;
use App\Enum\BlogPostStatus;

final class BlogPostIndexFilter
{
    private ?string $searchQuery = null;

    private ?BlogPostStatus $status = null;

    private ?Blog $blog = null;

    private ?BlogPostType $blogPostType = null;

    public function getSearchQuery(): ?string
    {
        return $this->searchQuery;
    }

    public function setSearchQuery(?string $searchQuery): BlogPostIndexFilter
    {
        $this->searchQuery = $searchQuery;

        return $this;
    }

    public function getStatus(): ?BlogPostStatus
    {
        return $this->status;
    }

    public function setStatus(?BlogPostStatus $status): BlogPostIndexFilter
    {
        $this->status = $status;

        return $this;
    }

    public function getBlog(): ?Blog
    {
        return $this->blog;
    }

    public function setBlog(?Blog $blog): BlogPostIndexFilter
    {
        $this->blog = $blog;

        return $this;
    }

    public function getBlogPostType(): ?BlogPostType
    {
        return $this->blogPostType;
    }

    public function setBlogPostType(?BlogPostType $blogPostType): BlogPostIndexFilter
    {
        $this->blogPostType = $blogPostType;

        return $this;
    }
}
