<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Security;

/**
 * Returns a 404 on the author archives and the `?author=` requests, and replaces the author links by the home URL.
 */
final class PreventUserEnumeration
{
    public function register(): void
    {
        add_action('template_redirect', [$this, 'blockAuthorRequests']);
        add_filter('author_link', fn (): string => home_url('/'));
    }

    public function blockAuthorRequests(): void
    {
        if (!isset($_GET['author']) && !is_author()) {
            return;
        }

        global $wp_query;
        if ($wp_query instanceof \WP_Query) {
            $wp_query->set_404();
        }

        status_header(404);
        nocache_headers();
    }
}
