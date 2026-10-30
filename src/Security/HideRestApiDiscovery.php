<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Security;

/**
 * Removes the links advertising the REST API (head, HTTP header and RSD).
 */
final class HideRestApiDiscovery
{
    public function register(): void
    {
        add_action('init', [$this, 'removeDiscoveryHints']);
    }

    public function removeDiscoveryHints(): void
    {
        remove_action('wp_head', 'rest_output_link_wp_head');
        remove_action('template_redirect', 'rest_output_link_header', 11);
        remove_action('xmlrpc_rsd_apis', 'rest_output_rsd');
    }
}
