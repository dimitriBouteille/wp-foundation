<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Routing;

class Route extends \Symfony\Component\Routing\Route
{
    /**
     * @inheritDoc
     * @param array<string, mixed> $defaults
     * @param array<string|\Stringable> $requirements
     * @param array<string, mixed> $options
     * @param string|string[] $schemes
     * @param string|string[] $methods
     */
    public function __construct(
        public readonly string $name,
        string $path,
        array $defaults = [],
        array $requirements = [],
        array $options = [],
        ?string $host = '',
        array|string $schemes = [],
        array|string $methods = [],
        ?string $condition = '',
    ) {
        parent::__construct(
            $path,
            $defaults,
            $requirements,
            $options,
            $host,
            $schemes,
            $methods,
            $condition,
        );
    }
}
