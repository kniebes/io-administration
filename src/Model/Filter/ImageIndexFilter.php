<?php declare(strict_types=1);

namespace App\Model\Filter;

class ImageIndexFilter
{
    private ?string $searchQuery = null;

    public function getSearchQuery(): ?string
    {
        return $this->searchQuery;
    }

    public function setSearchQuery(?string $searchQuery): BlogPostIndexFilter
    {
        $this->searchQuery = $searchQuery;

        return $this;
    }
}
