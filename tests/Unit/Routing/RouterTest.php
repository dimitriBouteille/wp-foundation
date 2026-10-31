<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Tests\Unit\Routing;

use Brain\Monkey\Functions;
use Dbout\WpFoundation\Routing\Route;
use Dbout\WpFoundation\Routing\Router;
use Dbout\WpFoundation\Tests\Fixtures\Http\RecordedResponse;
use Dbout\WpFoundation\Tests\TestCase;
use Illuminate\Container\Container;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class RouterTest extends TestCase
{
    /**
     * @var array<string, mixed>
     */
    private array $server;

    protected function setUp(): void
    {
        parent::setUp();

        $this->server = $_SERVER;
        RecordedResponse::$sent = [];
        Functions\when('home_url')->justReturn('https://example.com');
        Functions\when('wp_parse_url')->alias('parse_url');
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->server;
        parent::tearDown();
    }

    public function testMatchesTheCurrentRequestAndSendsTheResponse(): void
    {
        $router = $this->createRouter('/blog/42?page=2');

        $router->matchCurrentRoute();

        self::assertCount(1, RecordedResponse::$sent);
        $response = RecordedResponse::$sent[0];
        self::assertSame('blog_show', $response->action);
        self::assertSame(42, $response->data['id']);
        self::assertInstanceOf(Request::class, $response->data['request']);
        self::assertSame('2', $response->data['request']->query->get('page'));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function trailingSlashProvider(): iterable
    {
        yield 'without trailing slash' => ['/blog', 'blog_index'];
        yield 'with trailing slash' => ['/blog/', 'blog_index'];
        yield 'nested controller' => ['/admin/orders/AB-12/', 'admin_order'];
        yield 'home page' => ['/', 'home'];
        yield 'route with a trailing slash' => ['/account/register', 'register'];
        yield 'route with a trailing slash, requested with it' => ['/account/register/', 'register'];
    }

    #[DataProvider('trailingSlashProvider')]
    public function testTheTrailingSlashIsIgnored(string $uri, string $action): void
    {
        $router = $this->createRouter($uri);
        $router->add(new Route('register', '/account/register/', ['_controller' => fn (): RecordedResponse => new RecordedResponse('register')]));

        $router->matchCurrentRoute();

        self::assertSame($action, RecordedResponse::$sent[0]->action ?? null);
    }

    public function testLetsWordPressHandleTheUnknownUrls(): void
    {
        $this->createRouter('/unknown')->matchCurrentRoute();

        self::assertSame([], RecordedResponse::$sent);
    }

    public function testLetsWordPressHandleTheOtherHttpMethods(): void
    {
        $this->createRouter('/blog', 'POST')->matchCurrentRoute();

        self::assertSame([], RecordedResponse::$sent);
    }

    public function testRequirementsMustMatch(): void
    {
        $this->createRouter('/blog/first-post')->matchCurrentRoute();

        self::assertSame([], RecordedResponse::$sent);
    }

    #[RunInSeparateProcess]
    public function testDoesNotMatchTheRoutesUnderWpCli(): void
    {
        define('WP_CLI', true);

        $this->createRouter('/')->matchCurrentRoute();

        self::assertSame([], RecordedResponse::$sent);
    }

    public function testWorksWhenWordPressIsInstalledInASubdirectory(): void
    {
        Functions\when('home_url')->justReturn('https://example.com/blog/');
        $_SERVER['SCRIPT_NAME'] = $_SERVER['PHP_SELF'] = '/blog/index.php';
        $_SERVER['SCRIPT_FILENAME'] = '/var/www/blog/index.php';
        $router = $this->createRouter('/blog/cart');

        $router->matchCurrentRoute();

        self::assertSame('cart', RecordedResponse::$sent[0]->action ?? null);
        self::assertSame('/blog/blog/42', $router->generate('blog_show', ['id' => 42]));
        self::assertSame('https://example.com/blog/cart', $router->generate('cart', [], UrlGeneratorInterface::ABSOLUTE_URL));
    }

    public function testCachesTheRoutesOfTheNamespaces(): void
    {
        $cache = new ArrayAdapter();
        $namespaces = ['Dbout\\WpFoundation\\Tests\\Fixtures\\Modules\\*\\Controller\\'];
        $expected = $this->createRouter('/')->getRouteCollection();
        $this->createRouter('/', namespaces: [])->addNamespaces($namespaces, $cache);

        $router = $this->createRouter('/blog/42', namespaces: []);
        $router->addNamespaces($namespaces, $cache);

        self::assertCount(1, $cache->getValues());
        self::assertEquals($expected, $router->getRouteCollection());
        $route = $router->getRouteCollection()->get('blog_show');
        self::assertInstanceOf(Route::class, $route);
        self::assertSame('blog_show', $route->name);

        $router->matchCurrentRoute();
        self::assertSame(42, RecordedResponse::$sent[0]->data['id'] ?? null);
    }

    public function testReadsTheRoutesFromTheCacheWithoutScanningTheControllers(): void
    {
        $cache = new ArrayAdapter();
        $namespaces = ['Dbout\\WpFoundation\\Tests\\Fixtures\\Modules\\*\\Controller\\'];
        $cache->save($cache->getItem('app_routes_' . hash('xxh128', serialize($namespaces)))->set([
            ['name' => 'cached', 'path' => '/cached', 'defaults' => ['_controller' => 'strtoupper'], 'requirements' => [], 'methods' => ['GET']],
        ]));

        $router = $this->createRouter('/', namespaces: []);
        $router->addNamespaces($namespaces, $cache);

        self::assertSame(['cached'], array_keys($router->getRouteCollection()->all()));
        self::assertSame(['GET'], $router->getRouteCollection()->get('cached')?->getMethods());
    }

    public function testMatchesTheRoutesOnInit(): void
    {
        $router = $this->createRouter('/');
        $router->boot();

        self::assertSame(10, has_action('init', [$router, 'matchCurrentRoute']));
    }

    public function testGeneratesTheUrls(): void
    {
        $router = $this->createRouter('/');

        self::assertSame('/blog/42?page=2', $router->generate('blog_show', ['id' => 42, 'page' => 2]));
        self::assertSame('https://example.com/blog', $router->generate('blog_index', [], UrlGeneratorInterface::ABSOLUTE_URL));
    }

    public function testTheAbsoluteUrlsUseTheHomeUrl(): void
    {
        Functions\when('home_url')->justReturn('http://example.test:8080');
        $router = $this->createRouter('/');

        self::assertSame('http://example.test:8080/blog', $router->generate('blog_index', [], UrlGeneratorInterface::ABSOLUTE_URL));
    }

    public function testGeneratesTheCustomPaths(): void
    {
        $router = $this->createRouter('/', customPaths: ['home' => fn (): string => 'https://example.com/']);

        self::assertSame('https://example.com/', $router->generate('home'));
        self::assertSame('https://example.com/?s=first%20aid', $router->generate('home', ['s' => 'first aid']));
    }

    public function testAddsTheQueryToTheQueryOfTheCustomPath(): void
    {
        $router = $this->createRouter('/', customPaths: ['cart' => fn (): string => 'https://example.com/?page_id=5#items']);

        self::assertSame('https://example.com/?page_id=5&s=x#items', $router->generate('cart', ['s' => 'x']));
    }

    public function testUsesTheRouteWhenTheCustomPathReturnsNoUrl(): void
    {
        $router = $this->createRouter('/', customPaths: ['cart' => fn (): false => false]);

        self::assertSame('/cart', $router->generate('cart'));
    }

    public function testThrowsWhenTheCustomPathReturnsNoUrlWithoutRoute(): void
    {
        $router = $this->createRouter('/', customPaths: ['contact' => fn (): false => false]);

        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessage('The custom path "contact" returned no URL');

        $router->generate('contact');
    }

    public function testTheCustomPathMustBeCallable(): void
    {
        $router = $this->createRouter('/', customPaths: ['home' => 'https://example.com/']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The custom path "home" must be a callable.');

        $router->generate('home');
    }

    public function testThrowsWhenTheRouteDoesNotExist(): void
    {
        $this->expectException(RouteNotFoundException::class);

        $this->createRouter('/')->generate('unknown');
    }

    public function testAddsARoute(): void
    {
        $router = $this->createRouter('/contact');
        $router->add(new Route('contact', '/contact', ['_controller' => fn (): RecordedResponse => new RecordedResponse('contact')]));

        $router->matchCurrentRoute();

        self::assertSame('contact', RecordedResponse::$sent[0]->action ?? null);
    }

    /**
     * @param array<string, mixed> $customPaths
     * @param string[]|null $namespaces
     */
    private function createRouter(string $uri, string $method = 'GET', array $customPaths = [], ?array $namespaces = null): Router
    {
        $_SERVER['REQUEST_URI'] = $uri;
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['QUERY_STRING'] = (string) parse_url($uri, PHP_URL_QUERY);
        parse_str($_SERVER['QUERY_STRING'], $_GET);

        $router = new Router(new Container(), $customPaths);
        $router->addNamespaces($namespaces ?? ['Dbout\\WpFoundation\\Tests\\Fixtures\\Modules\\*\\Controller\\']);

        return $router;
    }
}
