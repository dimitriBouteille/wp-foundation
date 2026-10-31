<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Security;

/**
 * Returns a 404 on the author archives and the `?author=` requests, and replaces the author links by the home URL.
 *
 * The requests are blocked before `redirect_canonical()` (priority 10 of `template_redirect`), and the canonical
 * redirection is disabled for them: otherwise `?author=N` is redirected (301) for the users who published posts and
 * returns a 404 for the others, which still reveals the user IDs.
 */
final class PreventUserEnumeration
{
    public function register(): void
    {
        add_action('template_redirect', [$this, 'blockAuthorRequests'], 1);
        add_filter('author_link', fn (): string => home_url('/'));
    }

    public function blockAuthorRequests(): void
    {
        if (!isset($_GET['author']) && !is_author()) {
            return;
        }

        add_filter('redirect_canonical', '__return_false');

        global $wp_query;
        if ($wp_query instanceof \WP_Query) {
            $wp_query->set_404();
        }

        status_header(404);
        nocache_headers();
    }
}
