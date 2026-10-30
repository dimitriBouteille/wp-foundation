<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Routing;

use Dbout\WpFoundation\Application;

final readonly class RouterServiceProvider
{
    public function __construct(
        private Application $app,
    ) {}

    /**
     * Load the controllers once all the providers are registered, so that they can add their own `router.namespaces`.
     *
     * @throws \Throwable
     */
    public function boot(): void
    {
        $namespaces = $this->app->getConfig()->get('router.namespaces', []);
        if (!is_array($namespaces)) {
            $namespaces = [];
        }

        $customPaths = $this->app->getConfig()->get('router.customPaths', []);
        if (!is_array($customPaths)) {
            $customPaths = [];
        }

        $router = new Router(
            container: $this->app,
            customPaths: $customPaths,
        );

        $router->addNamespaces(array_filter($namespaces, is_string(...)));
        $router->boot();
        $this->app->instance('router', $router);
    }
}
