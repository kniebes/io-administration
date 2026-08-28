<?php declare(strict_types=1);

namespace App\Service\Navigation;

use App\Model\Navigation\NavigationItemCollection;
use App\Service\Navigation\Interface\NavigationAware;
use App\Service\Navigation\Interface\NavigationServiceInterface;
use ArrayIterator;

class NavigationService implements NavigationServiceInterface
{
    /**
     * @param iterable<NavigationAware> $handlers
     */
    public function __construct(
        private readonly iterable $handlers
    )
    {
    }


    public function getNavigationItems(): ArrayIterator
    {
        $navigationItemCollection = new NavigationItemCollection();
        foreach ($this->handlers as $handler) {
            $navigationItemCollection->addElements($handler->getNavigationItems());
        }

        return $navigationItemCollection->getSorted();
    }

}
