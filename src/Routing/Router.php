<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Routing;

use Dbout\WpFoundation\Routing\Generator\UrlGenerator;
use Dbout\WpFoundation\Routing\Loader\NamespaceLoader;
use Illuminate\Contracts\Container\Container;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

final class Router implements RouterInterface
{
    private const string CACHE_KEY = 'app_routes';

    protected UrlMatcher $urlMatcher;

    protected RequestContext $requestContext;

    protected RouteCollection $routeCollection;

    protected Request $request;

    protected ?UrlGenerator $generator = null;

    /**
     * Namespaces added with {@see addNamespaces()}, loaded on the first use of the routes.
     *
     * @var array<array{string[], CacheItemPoolInterface|null}>
     */
    private array $pendingNamespaces = [];

    /**
     * @param Container $container          Used to resolve and call the controllers
     * @param array<string, mixed> $customPaths
     */
    public function __construct(
        protected Container $container,
        protected array $customPaths = [],
    ) {
        $this->request = Request::createFromGlobals();
        $this->routeCollection = new RouteCollection();
        $this->requestContext = new RequestContext();
        $this->requestContext->fromRequest($this->request);
        $this->requestContext->setBaseUrl('');

        // Force the scheme/host/port and the base path from WordPress so the URLs are correct even
        // when generated outside a web request (WP-Cron, CLI, etc.) where the globals do not contain
        // a valid host, and when WordPress is installed in a subdirectory (https://example.com/blog).
        $home = wp_parse_url(home_url());
        if (is_array($home)) {
            $this->requestContext->setBaseUrl(rtrim(is_string($home['path'] ?? null) ? $home['path'] : '', '/'));
            $scheme = is_string($home['scheme'] ?? null) ? $home['scheme'] : 'https';
            $port = is_int($home['port'] ?? null) ? $home['port'] : null;
            $this->requestContext
                ->setScheme($scheme)
                ->setHost(is_string($home['host'] ?? null) ? $home['host'] : '');

            if ($scheme === 'https') {
                $this->requestContext->setHttpsPort($port ?? 443);
            } else {
                $this->requestContext->setHttpPort($port ?? 80);
            }
        }

        $this->urlMatcher = new UrlMatcher($this->routeCollection, $this->requestContext);
    }

    /**
     * @return $this
     */
    public function add(Route $route, int $priority = 0): self
    {
        $this->routeCollection->add($route->name, $route, $priority);

        return $this;
    }

    /**
     * Add the routes of the controllers of the given PSR-4 namespaces (e.g. `App\Controller\`).
     *
     * The controllers are discovered on the first use of the routes (matching on `init`, URL generation), not when
     * the namespaces are added: the application is created by a mu-plugin, before the plugins are loaded, and loading
     * a controller extending a class of a plugin (e.g. WooCommerce) would fail.
     *
     * Finding the controllers scans the directories and reads the attributes of every class: with a cache pool
     * (in production), the routes are cached by namespaces config, a new controller is found once the cache is cleared
     * (`wp app:cache:clear`).
     *
     * @param string[] $namespaces
     * @return $this
     */
    public function addNamespaces(array $namespaces, ?CacheItemPoolInterface $cache = null): self
    {
        $this->pendingNamespaces[] = [$namespaces, $cache];

        return $this;
    }

    public function boot(): void
    {
        add_action('init', [$this, 'matchCurrentRoute']);
    }

    /**
     * Executes the controller of the route matching the current request. The trailing slash is ignored:
     * `/account/register/` matches the `/account/register` route, and `/account/register` the `/account/register/` route.
     * Only the front-end requests are matched, see {@see shouldMatchCurrentRequest()}.
     *
     * @throws \Exception
     */
    public function matchCurrentRoute(): void
    {
        if (!$this->shouldMatchCurrentRequest()) {
            return;
        }

        $match = $this->matchIgnoringTheTrailingSlash($this->requestContext->getPathInfo());
        if ($match !== null) {
            $this->executeAction($match);
        }
    }

    /**
     * @inheritDoc
     * @return array<string, mixed>
     */
    public function match(string $pathinfo): array
    {
        return $this->getMatcher()->match($pathinfo);
    }

    /**
     * Gets the UrlGenerator instance associated with this Router.
     *
     * @throws \Exception
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getGenerator(): UrlGenerator
    {
        $this->loadPendingNamespaces();
        $this->generator ??= new UrlGenerator(
            routes: $this->routeCollection,
            context: $this->requestContext,
            customPaths: $this->customPaths,
        );

        return $this->generator;
    }

    /**
     * @inheritDoc
     * @throws \Exception
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getRouteCollection(): RouteCollection
    {
        $this->loadPendingNamespaces();

        return $this->routeCollection;
    }

    /**
     * @inheritDoc
     * @param array<string, mixed> $parameters
     */
    public function generate(string $name, array $parameters = [], int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): string
    {
        return $this->getGenerator()->generate($name, $parameters, $referenceType);
    }

    /**
     * @throws \Exception
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getMatcher(): UrlMatcher
    {
        $this->loadPendingNamespaces();

        return $this->urlMatcher;
    }

    /**
     * @inheritDoc
     */
    public function setContext(RequestContext $context): void
    {
        $this->requestContext = $context;
        $this->urlMatcher->setContext($context);
        $this->generator?->setContext($context);
    }

    /**
     * @inheritDoc
     */
    public function getContext(): RequestContext
    {
        return $this->requestContext;
    }

    /**
     * @param array<string, mixed> $config
     * @throws \Exception
     */
    protected function executeAction(array $config): void
    {
        /** @var callable|array{0: class-string|object, 1: string}|string|null $controller */
        $controller = $config['_controller'] ?? null;
        if ($controller === null) {
            throw new \Exception('Key _controller not found.');
        }

        unset($config['_controller']);
        $executor = new RouteExecutor($this->container, $controller, $config, $this->request);
        $executor->execute();
    }

    /**
     * Only the front-end requests served by `index.php` are matched: a route with a variable first segment (`/{slug}`)
     * must not take over the other entry points of WordPress (login, cron, admin, AJAX, XML-RPC, REST API).
     * Under WP-CLI, the request path is always `/`.
     *
     * The URLs of the routes can still be generated in these contexts (e.g. an email sent by a cron event).
     */
    private function shouldMatchCurrentRequest(): bool
    {
        if (
            (defined('WP_CLI') && constant('WP_CLI'))
            || (defined('XMLRPC_REQUEST') && constant('XMLRPC_REQUEST'))
            || is_admin()
            || wp_doing_ajax()
            || wp_doing_cron()
        ) {
            return false;
        }

        // wp-login.php, wp-cron.php, wp-signup.php, xmlrpc.php, ... (set by WordPress from the executed script)
        $page = $GLOBALS['pagenow'] ?? 'index.php';
        if ($page !== 'index.php') {
            return false;
        }

        // The REST API is served by index.php: /wp-json/... or ?rest_route=...
        $restPrefix = '/' . trim(rest_get_url_prefix(), '/') . '/';

        return !$this->request->query->has('rest_route')
            && !str_starts_with(rtrim($this->requestContext->getPathInfo(), '/') . '/', $restPrefix);
    }

    /**
     * @throws \Exception
     * @throws \Psr\Cache\InvalidArgumentException
     */
    private function loadPendingNamespaces(): void
    {
        $pending = $this->pendingNamespaces;
        $this->pendingNamespaces = [];
        foreach ($pending as [$namespaces, $cache]) {
            foreach ($this->loadNamespaces($namespaces, $cache) as $route) {
                $this->add($route);
            }
        }
    }

    /**
     * @param string[] $namespaces
     * @throws \Exception
     * @throws \Psr\Cache\InvalidArgumentException
     * @return Route[]
     */
    private function loadNamespaces(array $namespaces, ?CacheItemPoolInterface $cache): array
    {
        $item = $cache?->getItem(self::CACHE_KEY . '_' . hash('xxh128', serialize($namespaces)));
        $cached = $item?->isHit() === true ? $item->get() : null;
        if (is_array($cached)) {
            /** @var array<array{name: string, path: string, defaults: array<string, mixed>, requirements: array<string, string>, methods: string[]}> $cached */
            return array_map(fn (array $route): Route => new Route(
                name: $route['name'],
                path: $route['path'],
                defaults: $route['defaults'],
                requirements: $route['requirements'],
                methods: $route['methods'],
            ), $cached);
        }

        $loader = new NamespaceLoader();
        $routes = [];
        foreach ($namespaces as $namespace) {
            foreach ($loader->load($namespace) as $route) {
                $routes[] = $route;
            }
        }

        if ($cache instanceof CacheItemPoolInterface && $item instanceof CacheItemInterface) {
            // Plain arrays: a serialized Route would lose its name (Symfony Route serializes its own properties only)
            $item->set(array_map(fn (Route $route): array => [
                'name' => $route->name,
                'path' => $route->getPath(),
                'defaults' => $route->getDefaults(),
                'requirements' => $route->getRequirements(),
                'methods' => $route->getMethods(),
            ], $routes));
            $cache->save($item);
        }

        return $routes;
    }

    /**
     * @return array<string, mixed>|null Null when it is not a controller route (or not for this HTTP method): WordPress handles the request.
     */
    private function matchIgnoringTheTrailingSlash(string $pathinfo): ?array
    {
        $trimmed = rtrim($pathinfo, '/');
        $paths = [$pathinfo];
        if ($trimmed !== '') {
            $paths[] = $trimmed === $pathinfo ? $pathinfo . '/' : $trimmed;
        }

        foreach ($paths as $path) {
            try {
                return $this->match($path);
            } catch (ResourceNotFoundException|MethodNotAllowedException) {
            }
        }

        return null;
    }
}
