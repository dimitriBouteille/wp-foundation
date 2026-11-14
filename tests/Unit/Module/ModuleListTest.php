<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Tests\Unit\Module;

use Dbout\WpFoundation\Config\Repository;
use Dbout\WpFoundation\Module\Exception\ModuleException;
use Dbout\WpFoundation\Module\ModuleList;
use Dbout\WpFoundation\Tests\Fixtures\Modules\Blog\BlogModule;
use Dbout\WpFoundation\Tests\Fixtures\Modules\Shop\ShopModule;
use Dbout\WpFoundation\Tests\Fixtures\Modules\Shop\ShopServiceProvider;
use Dbout\WpFoundation\Tests\Fixtures\Modules\UserAccount\UserAccountModule;
use Dbout\WpFoundation\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class ModuleListTest extends TestCase
{
    public function testKeepsTheOrderOfDeclarationAndTheFirstInstanceOfAModule(): void
    {
        $blog = new BlogModule();
        $modules = new ModuleList([$blog, new UserAccountModule(), new ShopModule(), new BlogModule()]);

        self::assertSame([BlogModule::class, UserAccountModule::class, ShopModule::class], array_keys($modules->all()));
        self::assertSame($blog, $modules->get(BlogModule::class));
        self::assertCount(3, $modules);
        self::assertSame(array_keys($modules->all()), array_keys(iterator_to_array($modules)));
    }

    public function testModulesCanDependOnEachOtherAndBeDeclaredBeforeTheirDependencies(): void
    {
        $modules = new ModuleList([new ShopModule(), new UserAccountModule(), new BlogModule()]);

        self::assertTrue($modules->has(ShopModule::class));
        self::assertTrue($modules->has(UserAccountModule::class));
    }

    public function testThrowsWhenADependencyIsNotEnabled(): void
    {
        $this->expectException(ModuleException::class);
        $this->expectExceptionMessage(sprintf('The module %s requires the modules %s, %s', ShopModule::class, BlogModule::class, UserAccountModule::class));

        new ModuleList([new ShopModule()]);
    }

    public function testBuildsTheModulesGivenAsClassNamesWithTheFactory(): void
    {
        $built = [];
        $modules = ModuleList::create([BlogModule::class, new UserAccountModule(), ShopModule::class], function (string $class) use (&$built): object {
            $built[] = $class;

            return new $class();
        });

        self::assertSame([BlogModule::class, ShopModule::class], $built);
        self::assertCount(3, $modules);
        self::assertNull((new ModuleList())->get(BlogModule::class));
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function provideInvalidModules(): iterable
    {
        yield 'unknown class' => ['App\\UnknownModule'];
        yield 'class that is not a module' => [\stdClass::class];
        yield 'object that is not a module' => [new \stdClass()];
    }

    #[DataProvider('provideInvalidModules')]
    public function testThrowsWhenAModuleIsInvalid(mixed $module): void
    {
        $this->expectException(ModuleException::class);
        $this->expectExceptionMessage('must implement Dbout\\WpFoundation\\Module\\ModuleInterface');

        ModuleList::create([$module], fn (string $class): object => new $class());
    }

    public function testAddsTheConfigurationOfTheModulesToTheLists(): void
    {
        $config = new Repository([
            'router' => ['namespaces' => ['App\\Controller\\']],
            'console' => ['namespaces' => 'invalid'],
        ]);

        (new ModuleList([new BlogModule(), new UserAccountModule(), new ShopModule()]))->applyConfiguration($config);

        self::assertSame([
            'App\\Controller\\',
            'Dbout\\WpFoundation\\Tests\\Fixtures\\Modules\\Blog\\Controller\\',
            'Dbout\\WpFoundation\\Tests\\Fixtures\\Modules\\Shop\\Controller\\',
        ], $config->get('router.namespaces'));
        self::assertSame(['Dbout\\WpFoundation\\Tests\\Fixtures\\Modules\\Shop\\Console\\'], $config->get('console.namespaces'));
        self::assertSame([self::fixturePath('Modules/Blog'), self::fixturePath('Modules/UserAccount')], $config->get('twig.modules'));
        self::assertSame([self::fixturePath('Modules/Shop/Controller/Admin')], $config->get('shop.handlers'));
    }

    public function testReturnsTheProvidersAndAHashOfTheModules(): void
    {
        $modules = new ModuleList([new BlogModule(), new UserAccountModule(), new ShopModule()]);

        self::assertSame([ShopServiceProvider::class], $modules->getProviders());
        self::assertSame($modules->getHash(), (new ModuleList([new BlogModule(), new UserAccountModule(), new ShopModule()]))->getHash());
        self::assertNotSame($modules->getHash(), (new ModuleList([new BlogModule()]))->getHash());
    }
}
