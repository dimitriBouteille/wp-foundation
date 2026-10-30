<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Routing\Loader;

use Dbout\WpFoundation\Routing\Attribute\Route as AttributeRoute;
use Dbout\WpFoundation\Routing\Route;

class AnnotatedRouteLoader implements InterfaceLoader
{
    /**
     * @inheritDoc
     * @return Route[]
     */
    public function load(string $resource): array
    {
        if (!class_exists($resource)) {
            throw new \InvalidArgumentException(sprintf('Class "%s" does not exist.', $resource));
        }

        $class = new \ReflectionClass($resource);
        if ($class->isAbstract()) {
            throw new \InvalidArgumentException(sprintf(
                'Annotations from class "%s" cannot be read as it is abstract.',
                $class->getName(),
            ));
        }

        $classRoute = $class->getAttributes(AttributeRoute::class)[0] ?? null;
        $classRoute = $classRoute?->newInstance();
        if ($classRoute instanceof AttributeRoute) {
            $method = $class->getMethod('__invoke');

            return [
                $this->createRoute($class, $method, $classRoute),
            ];
        }

        $routes = [];
        foreach ($class->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            $route = $method->getAttributes(AttributeRoute::class)[0] ?? null;
            $route = $route?->newInstance();
            if (!$route instanceof AttributeRoute) {
                continue;
            }

            $routes[] = $this->createRoute($class, $method, $route);
        }

        return $routes;
    }

    /**
     * @param \ReflectionClass<object> $reflectionClass
     */
    protected function createRoute(
        \ReflectionClass $reflectionClass,
        \ReflectionMethod $method,
        AttributeRoute $route,
    ): Route {
        if ($route->name === null || $route->name === '') {
            throw new \InvalidArgumentException(sprintf(
                'The route "%s" of %s::%s() must have a name.',
                $route->path,
                $reflectionClass->getName(),
                $method->getName(),
            ));
        }

        return new Route(
            name: $route->name,
            path: $route->path,
            defaults: [
                '_controller' => [$reflectionClass->getName(), $method->getName()],
            ],
            requirements: $route->requirements,
            methods: $route->methods,
        );
    }
}
