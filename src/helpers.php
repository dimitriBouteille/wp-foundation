<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

use Illuminate\Container\Container;

if (!function_exists('app')) {
    /**
     * Get the available container instance or resolve a given abstract.
     *
     * @template T
     * @param class-string<T>|null $abstract
     * @return ($abstract is null ? Container : T)
     */
    function app(?string $abstract = null): mixed
    {
        $container = Container::getInstance();
        if ($abstract === null) {
            return $container;
        }

        return $container->make($abstract);
    }
}
