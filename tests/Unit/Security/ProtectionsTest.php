<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Tests\Unit\Security;

use Brain\Monkey\Functions;
use Dbout\WpFoundation\Security\DisableAdminRedirects;
use Dbout\WpFoundation\Security\DisableOEmbed;
use Dbout\WpFoundation\Security\DisableXmlRpc;
use Dbout\WpFoundation\Security\HideRestApiDiscovery;
use Dbout\WpFoundation\Security\HideWordPressVersion;
use Dbout\WpFoundation\Security\PreventUserEnumeration;
use Dbout\WpFoundation\Security\RestrictRestApi;
use Dbout\WpFoundation\Security\SecurityHeaders;
use Dbout\WpFoundation\Tests\TestCase;

final class ProtectionsTest extends TestCase
{
    public function testDisableXmlRpc(): void
    {
        $protection = new DisableXmlRpc();
        $protection->register();
        add_action('wp_head', 'rsd_link');
        add_action('wp_head', 'wlwmanifest_link');

        $protection->removeDiscoveryLinks();

        self::assertSame(10, has_filter('xmlrpc_enabled', '__return_false'));
        self::assertSame(['Content-Type' => 'text/html'], $protection->removePingbackHeader(['Content-Type' => 'text/html', 'X-Pingback' => '/xmlrpc.php']));
        self::assertFalse(has_action('wp_head', 'rsd_link'));
        self::assertFalse(has_action('wp_head', 'wlwmanifest_link'));
    }

    public function testHideWordPressVersion(): void
    {
        Functions\when('is_admin')->justReturn(false);
        Functions\expect('remove_query_arg')->once()->with('ver', '/style.css?ver=6.9')->andReturn('/style.css');
        $protection = new HideWordPressVersion();
        $protection->register();
        add_action('wp_head', 'wp_generator');

        $protection->removeGeneratorAction();

        self::assertFalse(has_action('wp_head', 'wp_generator'));
        self::assertSame(10, has_filter('the_generator', '__return_empty_string'));
        self::assertSame('/style.css', $protection->stripVersion('/style.css?ver=6.9'));
        self::assertSame('/style.css?v=2', $protection->stripVersion('/style.css?v=2'));
    }

    public function testHideWordPressVersionKeepsTheVersionInTheAdministration(): void
    {
        Functions\when('is_admin')->justReturn(true);
        Functions\expect('remove_query_arg')->never();

        self::assertSame('/style.css?ver=6.9', (new HideWordPressVersion())->stripVersion('/style.css?ver=6.9'));
    }

    public function testPreventUserEnumerationReturnsA404OnTheAuthorRequests(): void
    {
        Functions\when('is_author')->justReturn(false);
        Functions\expect('status_header')->once()->with(404);
        Functions\expect('nocache_headers')->once();
        $_GET['author'] = '1';

        try {
            (new PreventUserEnumeration())->blockAuthorRequests();
        } finally {
            unset($_GET['author']);
        }

        self::assertSame(10, has_filter('redirect_canonical', '__return_false'));
    }

    public function testPreventUserEnumerationIgnoresTheOtherRequests(): void
    {
        Functions\when('is_author')->justReturn(false);
        Functions\expect('status_header')->never();

        (new PreventUserEnumeration())->blockAuthorRequests();

        self::assertFalse(has_filter('redirect_canonical', '__return_false'));
    }

    public function testPreventUserEnumerationReplacesTheAuthorLinks(): void
    {
        Functions\when('home_url')->justReturn('https://example.com/');
        $protection = new PreventUserEnumeration();
        $protection->register();

        self::assertSame(1, has_action('template_redirect', [$protection, 'blockAuthorRequests']));
        self::assertSame(10, has_filter('author_link', 'function ()'));
    }

    public function testDisableOEmbed(): void
    {
        $protection = new DisableOEmbed();
        add_action('rest_api_init', 'wp_oembed_register_route');
        add_action('wp_head', 'wp_oembed_add_discovery_links');

        $protection->disableProvider();

        self::assertFalse(has_action('rest_api_init', 'wp_oembed_register_route'));
        self::assertFalse(has_action('wp_head', 'wp_oembed_add_discovery_links'));
        self::assertSame(
            ['^blog/?$' => 'index.php?pagename=blog'],
            $protection->removeEmbedRewrites([
                '^blog/?$' => 'index.php?pagename=blog',
                '^blog/embed/?$' => 'index.php?pagename=blog&embed=true',
            ]),
        );
    }

    public function testHideRestApiDiscovery(): void
    {
        add_action('wp_head', 'rest_output_link_wp_head');
        add_action('template_redirect', 'rest_output_link_header', 11);

        (new HideRestApiDiscovery())->removeDiscoveryHints();

        self::assertFalse(has_action('wp_head', 'rest_output_link_wp_head'));
        self::assertFalse(has_action('template_redirect', 'rest_output_link_header'));
    }

    public function testDisableAdminRedirects(): void
    {
        add_action('template_redirect', 'wp_redirect_admin_locations', 1000);

        (new DisableAdminRedirects())->removeRedirect();

        self::assertFalse(has_action('template_redirect', 'wp_redirect_admin_locations'));
    }

    public function testRestrictRestApiHidesTheRoutesOfThePrivateNamespaces(): void
    {
        Functions\expect('current_user_can')->once()->with('edit_posts')->andReturn(false);
        $endpoints = ['/' => [], '/wp/v2/posts' => [], '/app/v2/sessions' => [], '/app/v2' => [], '/wc/store/v1/cart' => [], '/app/v20/test' => []];

        $filtered = (new RestrictRestApi(['app/v2', '/wc/store/'], 'edit_posts'))->filterEndpoints($endpoints);

        self::assertSame(['/app/v2/sessions', '/wc/store/v1/cart'], array_keys($filtered));
    }

    public function testRestrictRestApiKeepsAllTheRoutesForTheUsersWithTheCapability(): void
    {
        Functions\when('current_user_can')->justReturn(true);
        $endpoints = ['/wp/v2/posts' => []];

        self::assertSame($endpoints, (new RestrictRestApi([], 'manage_options'))->filterEndpoints($endpoints));
    }

    public function testSecurityHeadersAreNotSentInTheAdministration(): void
    {
        Functions\when('is_admin')->justReturn(true);
        $headers = new SecurityHeaders(['X-Frame-Options' => 'SAMEORIGIN']);
        $headers->register();

        $headers->send();

        self::assertSame(10, has_action('send_headers', [$headers, 'send']));
    }
}
