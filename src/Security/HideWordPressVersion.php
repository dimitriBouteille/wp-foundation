<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Security;

/**
 * Removes the generator tag and the `ver` query argument of the front-end scripts and styles.
 */
final class HideWordPressVersion
{
    public function register(): void
    {
        add_action('init', [$this, 'removeGeneratorAction']);
        add_filter('the_generator', '__return_empty_string');
        add_filter('script_loader_src', [$this, 'stripVersion']);
        add_filter('style_loader_src', [$this, 'stripVersion']);
    }

    public function removeGeneratorAction(): void
    {
        remove_action('wp_head', 'wp_generator');
    }

    public function stripVersion(string $src): string
    {
        if (is_admin() || !str_contains($src, 'ver=')) {
            return $src;
        }

        return remove_query_arg('ver', $src);
    }
}
