<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Tests\Unit\Security;

use Dbout\WpFoundation\Bootstrap\LoadConfiguration;
use Dbout\WpFoundation\Security\SecurityServiceProvider;
use Dbout\WpFoundation\Tests\TestCase;

final class SecurityServiceProviderTest extends TestCase
{
    public function testTheDefaultConfigurationOnlyEnablesTheProtectionsWithoutVisibleEffect(): void
    {
        $this->boot(null);

        self::assertSame(1, has_action('init', 'Dbout\WpFoundation\Security\SecurityHeaders->send()'));
        self::assertNotFalse(has_filter('xmlrpc_enabled', '__return_false'));
        self::assertNotFalse(has_action('init', 'Dbout\WpFoundation\Security\HideRestApiDiscovery->removeDiscoveryHints()'));
        self::assertFalse(has_filter('the_generator'));
        self::assertFalse(has_action('template_redirect'));
        self::assertFalse(has_filter('rewrite_rules_array'));
        self::assertFalse(has_filter('rest_endpoints'));
    }

    public function testEnablesTheProtectionsOfTheProjectConfiguration(): void
    {
        $this->boot(self::fixturePath('config'));

        self::assertNotFalse(has_filter('the_generator', '__return_empty_string'));
        self::assertNotFalse(has_filter('rest_endpoints'));
    }

    public function testEnablesAllTheProtections(): void
    {
        $directory = $this->createTemporaryDirectory();
        file_put_contents($directory . '/security.php', '<?php return ["headers" => ["X-Frame-Options" => null], "hideVersion" => true, "preventUserEnumeration" => true, "disableOEmbed" => true, "disableAdminRedirects" => true];');

        $this->boot($directory);

        self::assertNotFalse(has_filter('the_generator'));
        self::assertNotFalse(has_action('template_redirect'));
        self::assertNotFalse(has_filter('author_link'));
        self::assertNotFalse(has_filter('rewrite_rules_array'));
        self::assertNotFalse(has_action('init', 'Dbout\WpFoundation\Security\DisableAdminRedirects->removeRedirect()'));
    }

    public function testDisablesAllTheProtections(): void
    {
        $directory = $this->createTemporaryDirectory();
        file_put_contents($directory . '/security.php', '<?php return ["headers" => ["X-Frame-Options" => null, "X-Content-Type-Options" => null, "Referrer-Policy" => null], "disableXmlRpc" => false, "hideRestApiDiscovery" => false];');

        $this->boot($directory);

        self::assertFalse(has_filter('xmlrpc_enabled'));
        self::assertFalse(has_action('init'));
    }

    private function boot(?string $configPath): void
    {
        $app = $this->createApplication();
        if ($configPath !== null) {
            $app->useConfigPath($configPath);
        }

        (new LoadConfiguration())->bootstrap($app);
        (new SecurityServiceProvider($app))->boot();
    }
}
