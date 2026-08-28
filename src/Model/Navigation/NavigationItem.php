<?php declare(strict_types=1);

namespace App\Model\Navigation;

readonly class NavigationItem
{
    public function __construct(
        private string $title,
        private string $routeName,
        private int $position,
    )
    {
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getRouteName(): string
    {
        return $this->routeName;
    }

    public function getPosition(): int
    {
        return $this->position;
    }
}
