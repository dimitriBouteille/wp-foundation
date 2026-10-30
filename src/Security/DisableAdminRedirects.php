<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Security;

/**
 * Disables the redirections of `/admin`, `/login`, `/dashboard`, `/wp-admin` and `/wp-login.php` to the admin and login pages,
 * so that a custom admin location is not revealed.
 */
final class DisableAdminRedirects
{
    public function register(): void
    {
        add_action('init', [$this, 'removeRedirect']);
    }

    public function removeRedirect(): void
    {
        remove_action('template_redirect', 'wp_redirect_admin_locations', 1000);
    }
}
