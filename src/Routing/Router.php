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
    protected UrlMatcher $urlMatcher;

    protected RequestContext $requestContext;

    protected RouteCollection $routeCollection;

    protected Request $request;

    protected ?UrlGenerator $generator = null;

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

        // Force the scheme/host/port from WordPress so absolute URLs are correct even
        // when generated outside a web request (WP-Cron, CLI, etc.) where the
        // globals do not contain a valid host.
        $home = wp_parse_url(home_url());
        if (is_array($home)) {
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
     * Add the routes of the controllers of the given PSR-4 namespaces (i.e. `App\Controller\`).
     *
     * @param string[] $namespaces
     * @throws \Exception
     * @return $this
     */
    public function addNamespaces(array $namespaces): self
    {
        $loader = new NamespaceLoader();
        foreach ($namespaces as $namespace) {
            foreach ($loader->load($namespace) as $route) {
                $this->add($route);
            }
        }

        return $this;
    }

    public function boot(): void
    {
        add_action('init', [$this, 'matchCurrentRoute']);
    }

    /**
     * @throws \Exception
     */
    public function matchCurrentRoute(): void
    {
        try {
            $match = $this->match(rtrim($this->requestContext->getPathInfo(), '/'));
            $this->executeAction($match);
        } catch (ResourceNotFoundException|MethodNotAllowedException) {
            // Not a controller route (or not for this HTTP method): WordPress handles the request.
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
     */
    public function getGenerator(): UrlGenerator
    {
        $this->generator ??= new UrlGenerator(
            routes: $this->routeCollection,
            context: $this->requestContext,
            customPaths: $this->customPaths,
        );

        return $this->generator;
    }

    /**
     * @inheritDoc
     */
    public function getRouteCollection(): RouteCollection
    {
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

    public function getMatcher(): UrlMatcher
    {
        return $this->urlMatcher;
    }

    /**
     * @inheritDoc
     */
    public function setContext(RequestContext $context): void
    {
        $this->requestContext = $context;
        $this->getMatcher()->setContext($context);

        if ($this->generator instanceof UrlGenerator) {
            $this->getGenerator()->setContext($context);
        }
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
}
