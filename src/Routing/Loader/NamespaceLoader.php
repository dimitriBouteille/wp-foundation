<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Routing\Loader;

use Dbout\WpFoundation\Support\ClassFinder;

/**
 * Loads the routes of the classes of a PSR-4 namespace, wildcards supported (e.g. `App\*\Controller\`), see {@see ClassFinder}.
 */
class NamespaceLoader implements InterfaceLoader
{
    public function __construct(
        protected ClassFinder $classFinder = new ClassFinder(),
        protected InterfaceLoader $annotationClassLoader = new AnnotatedRouteLoader(),
    ) {}

    /**
     * @inheritDoc
     */
    public function load(string $resource): array
    {
        $routes = [];
        foreach ($this->classFinder->find($resource) as $class) {
            foreach ($this->annotationClassLoader->load($class) as $route) {
                $routes[] = $route;
            }
        }

        return $routes;
    }
}
