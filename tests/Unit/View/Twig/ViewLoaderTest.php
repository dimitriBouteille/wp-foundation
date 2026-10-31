<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Tests\Unit\View\Twig;

use Dbout\WpFoundation\Tests\TestCase;
use Dbout\WpFoundation\View\Twig\ViewLoader;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

final class ViewLoaderTest extends TestCase
{
    public function testFindsTheViewsDirectoryOfTheModules(): void
    {
        $loader = new ViewLoader([self::fixturePath('Modules') . '/*'], null);

        self::assertSame([
            ['blog', self::fixturePath('Modules/Blog/views')],
            ['userAccount', self::fixturePath('Modules/UserAccount/views')],
        ], $loader->loadPathsWithNamespaces());
    }

    public function testCachesThePaths(): void
    {
        $cache = new ArrayAdapter();
        $paths = (new ViewLoader([self::fixturePath('Modules') . '/*'], $cache))->loadPathsWithNamespaces();

        self::assertSame($paths, (new ViewLoader([], $cache))->loadPathsWithNamespaces());
    }
}
