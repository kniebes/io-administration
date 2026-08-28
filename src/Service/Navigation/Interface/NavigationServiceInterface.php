<?php declare(strict_types=1);

namespace App\Service\Navigation\Interface;

use App\Model\Navigation\NavigationItem;
use ArrayIterator;

interface NavigationServiceInterface
{
    /**
     * @return array<NavigationItem>
     */
    public function getNavigationItems(): ArrayIterator;
}
