<?php declare(strict_types=1);

namespace App\Model\Filter;

final class BlogPostFilter
{
    private ?string $searchQuery = null;

    public function getSearchQuery(): ?string
    {
        return $this->searchQuery;
    }

    public function setSearchQuery(?string $searchQuery): BlogPostFilter
    {
        $this->searchQuery = $searchQuery;

        return $this;
    }
}
