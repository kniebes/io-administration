<?php declare(strict_types=1);

namespace App\Model\Filter;

class LinkIndexFilter
{
    private ?string $searchQuery = null;

    public function getSearchQuery(): ?string
    {
        return $this->searchQuery;
    }

    public function setSearchQuery(?string $searchQuery): LinkIndexFilter
    {
        $this->searchQuery = $searchQuery;

        return $this;
    }
}
