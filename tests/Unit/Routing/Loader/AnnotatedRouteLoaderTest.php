<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Tests\Unit\Routing\Loader;

use Dbout\WpFoundation\Routing\Loader\AnnotatedRouteLoader;
use Dbout\WpFoundation\Tests\Fixtures\Modules\Blog\Controller\PostController;
use Dbout\WpFoundation\Tests\Fixtures\Modules\Shop\Controller\CartController;
use Dbout\WpFoundation\Tests\Fixtures\Routing\AbstractRouteController;
use Dbout\WpFoundation\Tests\Fixtures\Routing\UnnamedRouteController;
use Dbout\WpFoundation\Tests\TestCase;

final class AnnotatedRouteLoaderTest extends TestCase
{
    public function testLoadsTheRoutesOfThePublicMethods(): void
    {
        $routes = (new AnnotatedRouteLoader())->load(PostController::class);

        self::assertSame(['home', 'blog_index', 'blog_show'], array_map(fn (\Dbout\WpFoundation\Routing\Route $route): string => $route->name, $routes));

        $show = $routes[2];
        self::assertSame('/blog/{id}', $show->getPath());
        self::assertSame(['GET'], $show->getMethods());
        self::assertSame('\d+', $show->getRequirement('id'));
        self::assertSame([PostController::class, 'show'], $show->getDefault('_controller'));
    }

    public function testTheRouteOfTheClassUsesTheInvokeMethod(): void
    {
        $routes = (new AnnotatedRouteLoader())->load(CartController::class);

        self::assertCount(1, $routes);
        self::assertSame('cart', $routes[0]->name);
        self::assertSame(['GET', 'POST'], $routes[0]->getMethods());
        self::assertSame([CartController::class, '__invoke'], $routes[0]->getDefault('_controller'));
    }

    public function testThrowsWhenTheRouteHasNoName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The route "/unnamed" of ' . UnnamedRouteController::class . '::index() must have a name.');

        (new AnnotatedRouteLoader())->load(UnnamedRouteController::class);
    }

    public function testThrowsWhenTheClassIsAbstract(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('it is abstract');

        (new AnnotatedRouteLoader())->load(AbstractRouteController::class);
    }

    public function testThrowsWhenTheClassDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Class "Unknown\Controller" does not exist.');

        (new AnnotatedRouteLoader())->load('Unknown\Controller');
    }
}
