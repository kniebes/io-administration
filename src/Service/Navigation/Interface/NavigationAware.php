<?php declare(strict_types=1);

namespace App\Service\Navigation\Interface;

use App\Model\Navigation\NavigationItem;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.navigation_aware')]
interface NavigationAware
{
    /**
     * @return array<NavigationItem>
     */
    public function getNavigationItems(): array;
}
