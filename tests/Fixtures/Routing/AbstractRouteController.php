<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Tests\Fixtures\Routing;

use Dbout\WpFoundation\Routing\Attribute\Route;

abstract class AbstractRouteController
{
    #[Route('/abstract', name: 'abstract')]
    public function index(): void {}
}
