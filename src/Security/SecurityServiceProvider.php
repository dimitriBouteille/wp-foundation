<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Security;

use Dbout\WpFoundation\Application;

/**
 * Hardens WordPress according to the `security` configuration.
 */
final readonly class SecurityServiceProvider
{
    private const array PROTECTIONS = [
        'disableXmlRpc' => DisableXmlRpc::class,
        'hideVersion' => HideWordPressVersion::class,
        'preventUserEnumeration' => PreventUserEnumeration::class,
        'disableOEmbed' => DisableOEmbed::class,
        'hideRestApiDiscovery' => HideRestApiDiscovery::class,
        'disableAdminRedirects' => DisableAdminRedirects::class,
    ];

    public function __construct(
        private Application $app,
    ) {}

    /**
     * @throws \Throwable
     */
    public function boot(): void
    {
        $config = $this->app->getConfig();

        $headers = $config->get('security.headers', []);
        if (is_array($headers) && $headers !== []) {
            (new SecurityHeaders(array_filter($headers, fn (mixed $value): bool => is_string($value) || $value === null)))->register();
        }

        foreach (self::PROTECTIONS as $option => $class) {
            if ($config->get('security.' . $option) === true) {
                (new $class())->register();
            }
        }

        if ($config->get('security.restApi.restricted') === true) {
            $namespaces = $config->get('security.restApi.publicNamespaces', []);
            $capability = $config->get('security.restApi.capability');
            (new RestrictRestApi(
                publicNamespaces: is_array($namespaces) ? array_filter($namespaces, is_string(...)) : [],
                capability: is_string($capability) ? $capability : 'manage_options',
            ))->register();
        }
    }
}
