<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\View;

use Dbout\WpFoundation\Application;
use Dbout\WpFoundation\View\Resolver\ResolverInterface;

/**
 * Asset Service Provider
 *
 * This service provider is responsible for registering the asset management
 * system within the application container.
 *
 * It registers a singleton instance of AssetResolver that handles asset URL
 * resolution based on configured areas (frontend, admin, etc.).
 *
 * Configuration is loaded from the 'asset.areas' config key.
 *
 * @see AssetResolver
 */
final readonly class AssetServiceProvider
{
    /**
     * @param Application $app The framework application instance.
     */
    public function __construct(
        private Application $app,
    ) {}

    /**
     * Register the asset service in the application container.
     *
     * Registers an 'asset' singleton that provides an AssetResolver instance
     * configured with the areas defined in the application configuration.
     */
    public function register(): void
    {
        $this->app->singleton('asset', function (Application $app): AssetResolver {
            $areas = $app->getConfig()->get('asset.areas');
            $resolvers = [];
            foreach (is_array($areas) ? $areas : [] as $name => $resolver) {
                if (!$resolver instanceof ResolverInterface) {
                    throw new \Exception(sprintf('The asset area "%s" must implement %s', $name, ResolverInterface::class));
                }

                $resolvers[(string) $name] = $resolver;
            }

            return new AssetResolver($resolvers);
        });
    }
}
