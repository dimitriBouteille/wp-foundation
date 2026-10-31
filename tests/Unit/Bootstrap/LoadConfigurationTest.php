<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Tests\Unit\Bootstrap;

use Dbout\WpFoundation\Bootstrap\LoadConfiguration;
use Dbout\WpFoundation\Config\Repository;
use Dbout\WpFoundation\Tests\TestCase;

final class LoadConfigurationTest extends TestCase
{
    public function testLoadsThePackageConfigurationWithoutProjectConfiguration(): void
    {
        $config = $this->load(null);

        self::assertTrue($config->get('security.disableXmlRpc'));
        self::assertFalse($config->get('security.hideVersion'));
        self::assertSame([], $config->get('router.namespaces'));
        self::assertSame('app', $config->get('logging.default'));
    }

    public function testTheProjectOptionsReplaceTheDefaultOnes(): void
    {
        $config = $this->load(self::fixturePath('config'));

        self::assertTrue($config->get('security.hideVersion'));
        self::assertTrue($config->get('security.disableXmlRpc'));
    }

    public function testTheMergeableListsAreAddedToTheDefaultOnes(): void
    {
        $config = $this->load(self::fixturePath('config'));

        self::assertSame([
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=()',
        ], $config->get('security.headers'));
        self::assertSame([
            'restricted' => true,
            'publicNamespaces' => [],
            'capability' => 'manage_options',
        ], $config->get('security.restApi'));
        self::assertSame(['Dbout\\WpFoundation\\Tests\\Fixtures\\Modules\\*\\Controller\\'], $config->get('router.namespaces'));
        self::assertArrayHasKey('home', (array) $config->get('router.customPaths'));
    }

    public function testLoadsTheProjectFilesWithoutDefaultConfiguration(): void
    {
        $config = $this->load(self::fixturePath('config'));

        self::assertSame('value', $config->get('custom.key'));
        self::assertSame('stripe', $config->get('nested.payment.provider'));
    }

    public function testThrowsWhenAFileDoesNotReturnAnArray(): void
    {
        $directory = $this->createTemporaryDirectory();
        file_put_contents($directory . '/invalid.php', '<?php return "invalid";');

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('must return an array');

        $this->load($directory);
    }

    private function load(?string $configPath): Repository
    {
        $app = $this->createApplication();
        if ($configPath !== null) {
            $app->useConfigPath($configPath);
        }

        (new LoadConfiguration())->bootstrap($app);

        return $app->getConfig();
    }
}
