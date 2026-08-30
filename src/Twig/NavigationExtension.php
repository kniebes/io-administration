<?php declare(strict_types=1);

namespace App\Twig;

use App\Service\ErrorLogger\Interface\ErrorLoggerInterface;
use App\Service\Navigation\Interface\NavigationServiceInterface;
use ArrayIterator;
use Exception;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class NavigationExtension extends AbstractExtension
{
    public function __construct(
        private readonly NavigationServiceInterface $navigationService,
        private readonly ErrorLoggerInterface $errorLogger,
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
        try {
            return $this->navigationService->getNavigationItems();
        } catch (Exception $e) {
            $this->errorLogger->log($e);

            return new ArrayIterator();
        }
    }
}
