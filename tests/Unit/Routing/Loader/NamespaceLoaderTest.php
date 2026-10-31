<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Tests\Unit\Routing\Loader;

use Dbout\WpFoundation\Routing\Loader\NamespaceLoader;
use Dbout\WpFoundation\Tests\TestCase;

final class NamespaceLoaderTest extends TestCase
{
    public function testLoadsTheRoutesOfTheClassesOfTheNamespace(): void
    {
        $routes = (new NamespaceLoader())->load('Dbout\\WpFoundation\\Tests\\Fixtures\\Modules\\*\\Controller\\');

        self::assertSame(
            ['home', 'blog_index', 'blog_show', 'admin_order', 'cart'],
            array_map(fn (\Dbout\WpFoundation\Routing\Route $route): string => $route->name, $routes),
        );
    }
}
