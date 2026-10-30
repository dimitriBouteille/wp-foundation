<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Routing\Loader;

use Dbout\WpFoundation\Routing\Route;

interface InterfaceLoader
{
    /**
     * Loads the routes of a resource.
     *
     * @param string $resource The resource
     * @throws \Exception If something went wrong
     * @return Route[]
     */
    public function load(string $resource): array;
}
