<?php declare(strict_types=1);

namespace App\Service\Navigation;

use App\Model\Navigation\NavigationItem;
use App\Model\Navigation\NavigationItemCollection;
use App\Service\Navigation\Interface\NavigationServiceInterface;
use ArrayIterator;

readonly class NavigationService implements NavigationServiceInterface
{
    /**
     * @param array<NavigationItem> $navigationItems
     */
    public function __construct(
        private array $navigationItems
    )
    {
    }


    /**
     * @return ArrayIterator<NavigationItem>
     * @throws \Exception
     */
    public function getNavigationItems(): ArrayIterator
    {
        $navigationItemCollection = new NavigationItemCollection();
        $navigationItemCollection->addElements($this->navigationItems);

        return $navigationItemCollection->getSorted();
    }

}
