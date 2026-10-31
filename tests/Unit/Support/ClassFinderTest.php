<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Tests\Unit\Support;

use Composer\Autoload\ClassLoader;
use Dbout\WpFoundation\Support\ClassFinder;
use Dbout\WpFoundation\Tests\Fixtures\Modules\Blog\Controller\PostController;
use Dbout\WpFoundation\Tests\Fixtures\Modules\Shop\Console\SyncCommand;
use Dbout\WpFoundation\Tests\Fixtures\Modules\Shop\Console\SyncHelper;
use Dbout\WpFoundation\Tests\Fixtures\Modules\Shop\Controller\Admin\OrderController;
use Dbout\WpFoundation\Tests\Fixtures\Modules\Shop\Controller\CartController;
use Dbout\WpFoundation\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class ClassFinderTest extends TestCase
{
    private const string MODULES = 'Dbout\\WpFoundation\\Tests\\Fixtures\\Modules\\';

    public function testFindsTheConcreteClassesOfTheNamespaceRecursively(): void
    {
        $classes = (new ClassFinder())->find(self::MODULES . 'Shop\\Controller\\');

        self::assertSame([OrderController::class, CartController::class], $classes);
    }

    public function testFindsTheClassesOfTheDirectoriesMatchingTheWildcard(): void
    {
        $classes = (new ClassFinder())->find(self::MODULES . '*\\Controller\\');

        self::assertSame([PostController::class, OrderController::class, CartController::class], $classes);
    }

    public function testFindsTheClassesWithSeveralWildcards(): void
    {
        $classes = (new ClassFinder())->find(self::MODULES . '*\\*\\');

        self::assertContains(PostController::class, $classes);
        self::assertContains(SyncCommand::class, $classes);
        self::assertContains(SyncHelper::class, $classes);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function namespaceFormatProvider(): iterable
    {
        yield 'without trailing backslash' => [self::MODULES . 'Shop\\Console'];
        yield 'with leading backslash' => ['\\' . self::MODULES . 'Shop\\Console\\'];
    }

    #[DataProvider('namespaceFormatProvider')]
    public function testNormalizesTheNamespace(string $namespace): void
    {
        self::assertSame([SyncCommand::class, SyncHelper::class], (new ClassFinder())->find($namespace));
    }

    public function testThrowsWhenNoDirectoryIsFoundForTheNamespace(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No directory found for the namespace "Unknown\\Controller\\"');

        (new ClassFinder())->find('Unknown\\Controller\\');
    }

    public function testReturnsNothingWhenNoDirectoryMatchesTheWildcard(): void
    {
        self::assertSame([], (new ClassFinder())->find(self::MODULES . '*\\Unknown\\'));
    }

    public function testOnlyUsesTheGivenClassLoaders(): void
    {
        $loader = new ClassLoader();
        $loader->addPsr4(self::MODULES, self::fixturePath('Modules'));

        self::assertSame([SyncCommand::class, SyncHelper::class], (new ClassFinder([$loader]))->find(self::MODULES . '*\\Console\\'));
        self::assertSame([], (new ClassFinder([]))->find(self::MODULES . '*\\Console\\'));
    }
}
