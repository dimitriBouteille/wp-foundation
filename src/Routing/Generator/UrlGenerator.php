<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Routing\Generator;

use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;

final class UrlGenerator extends \Symfony\Component\Routing\Generator\UrlGenerator
{
    /**
     * @param array<string, mixed> $customPaths
     */
    public function __construct(
        RouteCollection $routes,
        RequestContext $context,
        ?LoggerInterface $logger = null,
        ?string $defaultLocale = null,
        protected array $customPaths = [],
    ) {
        parent::__construct($routes, $context, $logger, $defaultLocale);
    }

    /**
     * The custom path of the name is used first. When it returns no URL (e.g. `get_permalink()` returns false
     * because the page does not exist), the route of the same name is used, if any.
     *
     * @inheritDoc
     * @param array<string, mixed> $parameters
     */
    public function generate(
        string $name,
        array $parameters = [],
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH,
    ): string {
        if (isset($this->customPaths[$name])) {
            $custom = $this->getCustomPath($name, $parameters);
            if ($custom !== null) {
                return $custom;
            }

            if ($this->routes->get($name) === null) {
                throw new RouteNotFoundException(sprintf('The custom path "%s" returned no URL (e.g. the page does not exist), and there is no route with this name.', $name));
            }
        }

        return parent::generate($name, $parameters, $referenceType);
    }

    /**
     * @param array<string, mixed> $params
     * @return string|null Null when the custom path returns no URL
     */
    private function getCustomPath(string $name, array $params): ?string
    {
        $custom = $this->customPaths[$name];
        if (!is_callable($custom)) {
            throw new \InvalidArgumentException(sprintf('The custom path "%s" must be a callable.', $name));
        }

        $url = $custom();
        if (!is_string($url) || $url === '') {
            return null;
        }

        $query = http_build_query($params, '', '&', \PHP_QUERY_RFC3986);
        if ($query === '') {
            return $url;
        }

        // The query is added to the query of the URL (plain permalinks: `?page_id=5`), before its fragment
        $fragment = '';
        if (($position = strpos($url, '#')) !== false) {
            $fragment = substr($url, $position);
            $url = substr($url, 0, $position);
        }

        return $url . (str_contains($url, '?') ? '&' : '?') . $query . $fragment;
    }
}
