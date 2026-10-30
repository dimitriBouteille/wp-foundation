<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Routing\Generator;

use Psr\Log\LoggerInterface;
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
     * @inheritDoc
     * @param array<string, mixed> $parameters
     */
    public function generate(
        string $name,
        array $parameters = [],
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH,
    ): string {
        $custom = $this->getCustomPath($name, $parameters);
        if ($custom !== null && $custom !== '') {
            return $custom;
        }

        return parent::generate($name, $parameters, $referenceType);
    }

    /**
     * @param array<string, mixed> $params
     */
    private function getCustomPath(string $name, array $params): ?string
    {
        if (!isset($this->customPaths[$name])) {
            return null;
        }

        $custom = $this->customPaths[$name];
        if (!is_callable($custom)) {
            throw new \InvalidArgumentException(sprintf('The custom path "%s" must be a callable.', $name));
        }

        $process = $custom();
        $query = http_build_query($params, '', '&', \PHP_QUERY_RFC3986);
        if ($query === '') {
            return $process;
        }

        return $process . '?' . $query;
    }
}
