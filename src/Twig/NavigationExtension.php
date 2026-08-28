<?php declare(strict_types=1);

namespace App\Twig;

use App\Service\Navigation\Interface\NavigationServiceInterface;
use ArrayIterator;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class NavigationExtension extends AbstractExtension
{
    public function __construct(
        private readonly NavigationServiceInterface $navigationService
    )
    {
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('getNavigationElements', [$this, 'getNavigationElements']),
        ];
    }

    public function getNavigationElements(): ArrayIterator
    {
        return $this->navigationService->getNavigationItems();
    }
}
