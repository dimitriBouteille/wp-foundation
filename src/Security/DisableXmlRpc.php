<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Security;

final class DisableXmlRpc
{
    public function register(): void
    {
        add_filter('xmlrpc_enabled', '__return_false');
        add_filter('wp_headers', [$this, 'removePingbackHeader']);
        add_action('init', [$this, 'removeDiscoveryLinks']);
    }

    /**
     * @param array<string, string> $headers
     * @return array<string, string>
     */
    public function removePingbackHeader(array $headers): array
    {
        unset($headers['X-Pingback']);

        return $headers;
    }

    public function removeDiscoveryLinks(): void
    {
        remove_action('wp_head', 'rsd_link');
        remove_action('wp_head', 'wlwmanifest_link');
    }
}
