<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Tests\Unit\Module;

use Dbout\WpFoundation\Module\AbstractModule;
use Dbout\WpFoundation\Tests\Fixtures\Modules\Blog\BlogModule;
use Dbout\WpFoundation\Tests\Fixtures\Modules\Shop\ShopModule;
use Dbout\WpFoundation\Tests\Fixtures\Modules\UserAccount\UserAccountModule;
use Dbout\WpFoundation\Tests\TestCase;

final class AbstractModuleTest extends TestCase
{
    public function testFindsTheControllersAndTheViews(): void
    {
        self::assertSame([
            'router.namespaces' => ['Dbout\\WpFoundation\\Tests\\Fixtures\\Modules\\Blog\\Controller\\'],
            'twig.modules' => [self::fixturePath('Modules/Blog')],
        ], (new BlogModule())->getConfiguration());
    }

    public function testFindsTheCommandsAndMergesTheConfigurationOfTheModule(): void
    {
        self::assertSame([
            'router.namespaces' => ['Dbout\\WpFoundation\\Tests\\Fixtures\\Modules\\Shop\\Controller\\'],
            'console.namespaces' => ['Dbout\\WpFoundation\\Tests\\Fixtures\\Modules\\Shop\\Console\\'],
            'shop.handlers' => [self::fixturePath('Modules/Shop/Controller/Admin')],
        ], (new ShopModule())->getConfiguration());
    }

    public function testOnlyAddsTheExistingDirectories(): void
    {
        self::assertSame(['twig.modules' => [self::fixturePath('Modules/UserAccount')]], (new UserAccountModule())->getConfiguration());
        self::assertSame([], (new class () extends AbstractModule {})->getConfiguration());
    }

    public function testThePathAndTheNamespaceAreTheOnesOfTheModuleClass(): void
    {
        $module = new ShopModule();

        self::assertSame(self::fixturePath('Modules/Shop'), $module->getPath());
        self::assertSame('Dbout\\WpFoundation\\Tests\\Fixtures\\Modules\\Shop', $module->getNamespace());
        self::assertSame([], (new BlogModule())->getDependencies());
        self::assertSame([], (new BlogModule())->getProviders());
    }
}
