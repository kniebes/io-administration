<?php declare(strict_types=1);

namespace App\DependencyInjection\Compiler;

use App\Model\Navigation\NavigationItem;
use App\Service\Navigation\NavigationService;
use InvalidArgumentException;
use ReflectionAttribute;
use ReflectionMethod;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\Routing\Attribute\Route;

final class NavigationPass implements CompilerPassInterface
{
    private const string CONTROLLER_TAG = 'controller.service_arguments';
    private const string OPTION_KEY = 'navigation';

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(NavigationService::class)) {
            return;
        }

        $navigationItems = [];
        foreach (array_keys($container->findTaggedServiceIds(self::CONTROLLER_TAG)) as $serviceId) {
            $className = $container->getDefinition($serviceId)->getClass();
            if ($className === null) {
                continue;
            }

            $reflectionClass = $container->getReflectionClass($className, false);
            if ($reflectionClass === null) {
                continue;
            }

            foreach ($reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC) as $reflectionMethod) {
                foreach ($this->extractItems($reflectionMethod) as $navigationItem) {
                    $navigationItems[] = $navigationItem;
                }
            }
        }

        $container->getDefinition(NavigationService::class)->setArgument('$navigationItems', $navigationItems);
    }

    /**
     * @return array<Definition>
     */
    private function extractItems(ReflectionMethod $reflectionMethod): array
    {
        $navigationItems = [];
        $attributes = $reflectionMethod->getAttributes(Route::class, ReflectionAttribute::IS_INSTANCEOF);

        foreach ($attributes as $attribute) {
            $route = $attribute->newInstance();
            $navigation = $route->options[self::OPTION_KEY] ?? null;
            if ($navigation === null) {
                continue;
            }

            $origin = $reflectionMethod->getDeclaringClass()->getName() . '::' . $reflectionMethod->getName() . '()';

            if (!is_array($navigation) || !isset($navigation['title'], $navigation['position'])) {
                throw new InvalidArgumentException(
                    'The navigation option on ' . $origin . ' must be an array with the keys "title" and "position".'
                );
            }

            if ($route->name === null || $route->name === '') {
                throw new InvalidArgumentException(
                    'The route on ' . $origin . ' declares a navigation option but has no name.'
                );
            }

            $navigationItems[] = new Definition(NavigationItem::class, [
                '$title' => (string) $navigation['title'],
                '$routeName' => $route->name,
                '$position' => (int) $navigation['position'],
            ]);
        }

        return $navigationItems;
    }
}
