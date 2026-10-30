<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Security;

final class DisableOEmbed
{
    public function register(): void
    {
        add_action('init', [$this, 'disableProvider']);
        add_filter('rewrite_rules_array', [$this, 'removeEmbedRewrites']);
    }

    public function disableProvider(): void
    {
        remove_action('rest_api_init', 'wp_oembed_register_route');
        remove_action('wp_head', 'wp_oembed_add_discovery_links');
        remove_action('wp_head', 'wp_oembed_add_host_js');
    }

    /**
     * @param array<string, string> $rules
     * @return array<string, string>
     */
    public function removeEmbedRewrites(array $rules): array
    {
        foreach ($rules as $rule => $rewrite) {
            if (str_contains($rewrite, 'embed=true')) {
                unset($rules[$rule]);
            }
        }

        return $rules;
    }
}
