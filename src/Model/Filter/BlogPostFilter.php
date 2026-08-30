<?php declare(strict_types=1);

namespace App\Model\Filter;

use App\Entity\Blog;
use App\Entity\BlogPostType;
use App\Enum\BlogPostStatus;

final class BlogPostFilter
{
    private ?string $searchQuery = null;

    private ?BlogPostStatus $status = null;

    private ?Blog $blog = null;

    private ?BlogPostType $blogPostType = null;

    public function getSearchQuery(): ?string
    {
        return $this->searchQuery;
    }

    public function setSearchQuery(?string $searchQuery): BlogPostFilter
    {
        $this->searchQuery = $searchQuery;

        return $this;
    }

    public function getStatus(): ?BlogPostStatus
    {
        return $this->status;
    }

    public function setStatus(?BlogPostStatus $status): BlogPostFilter
    {
        $this->status = $status;

        return $this;
    }

    public function getBlog(): ?Blog
    {
        return $this->blog;
    }

    public function setBlog(?Blog $blog): BlogPostFilter
    {
        $this->blog = $blog;

        return $this;
    }

    public function getBlogPostType(): ?BlogPostType
    {
        return $this->blogPostType;
    }

    public function setBlogPostType(?BlogPostType $blogPostType): BlogPostFilter
    {
        $this->blogPostType = $blogPostType;

        return $this;
    }
}
