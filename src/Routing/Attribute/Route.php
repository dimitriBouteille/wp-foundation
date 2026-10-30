<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Routing\Attribute;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::TARGET_CLASS)]
readonly class Route
{
    /**
     * @param string $path                              The route path (e.g. "/user/login")
     * @param string|null $name                         The route name (e.g. "app_user_login")
     * @param string[] $methods                         The list of HTTP methods allowed by this route
     * @param array<string|\Stringable> $requirements   Requirements for the route attributes, @see https://symfony.com/doc/current/routing.html#parameters-validation
     */
    public function __construct(
        public string $path,
        public ?string $name,
        public array $methods = [],
        public array $requirements = [],
    ) {}
}
