<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Security;

/**
 * Hides the REST routes to the users without the capability, except the routes of the public namespaces.
 */
final readonly class RestrictRestApi
{
    /**
     * @param string[] $publicNamespaces Namespaces available to everyone (i.e. "app/v2")
     */
    public function __construct(
        private array $publicNamespaces,
        private string $capability,
    ) {}

    public function register(): void
    {
        add_filter('rest_endpoints', [$this, 'filterEndpoints']);
    }

    /**
     * @param array<string, mixed> $endpoints
     * @return array<string, mixed>
     */
    public function filterEndpoints(array $endpoints): array
    {
        if (current_user_can($this->capability)) {
            return $endpoints;
        }

        foreach (array_keys($endpoints) as $route) {
            if (!$this->isPublicRoute((string) $route)) {
                unset($endpoints[$route]);
            }
        }

        return $endpoints;
    }

    private function isPublicRoute(string $route): bool
    {
        foreach ($this->publicNamespaces as $namespace) {
            if (str_starts_with($route, '/' . trim($namespace, '/') . '/')) {
                return true;
            }
        }

        return false;
    }
}
