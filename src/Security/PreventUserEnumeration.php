<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Security;

/**
 * Returns a 404 on the author archives and the `?author=` requests, replaces the author links by the home URL,
 * and hides the users routes of the REST API (`/wp/v2/users`) to the visitors who are not logged in.
 *
 * The requests are blocked before `redirect_canonical()` (priority 10 of `template_redirect`), and the canonical
 * redirection is disabled for them: otherwise `?author=N` is redirected (301) for the users who published posts and
 * returns a 404 for the others, which still reveals the user IDs.
 *
 * The REST users routes stay available to the logged-in users: the block editor uses them (author selector, current user).
 */
final class PreventUserEnumeration
{
    public function register(): void
    {
        add_action('template_redirect', [$this, 'blockAuthorRequests'], 1);
        add_filter('author_link', fn (): string => home_url('/'));
        add_filter('rest_endpoints', [$this, 'hideUserEndpoints']);
    }

    /**
     * @param array<string, mixed> $endpoints
     * @return array<string, mixed>
     */
    public function hideUserEndpoints(array $endpoints): array
    {
        if (is_user_logged_in()) {
            return $endpoints;
        }

        foreach (array_keys($endpoints) as $route) {
            if (preg_match('#^/wp/v2/users(/|$)#', (string) $route) === 1) {
                unset($endpoints[$route]);
            }
        }

        return $endpoints;
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
