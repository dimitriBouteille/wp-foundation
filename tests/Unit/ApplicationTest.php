<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Tests\Unit;

use Dbout\WpFoundation\Application;
use Dbout\WpFoundation\Tests\Fixtures\Providers\RecordingProvider;
use Dbout\WpFoundation\Tests\TestCase;
use Illuminate\Container\Container;

final class ApplicationTest extends TestCase
{
    public function testIsTheGlobalContainer(): void
    {
        $app = new Application('/var/www/project/');

        self::assertSame($app, Container::getInstance());
        self::assertSame($app, app());
        self::assertSame($app, app(Application::class));
        self::assertSame('/var/www/project', $app->getBasePath());
    }

    public function testRegistersTheProviderOnce(): void
    {
        $app = $this->createApplication();

        $provider = $app->register(RecordingProvider::class);

        self::assertInstanceOf(RecordingProvider::class, $provider);
        self::assertSame($app, $provider->app);
        self::assertSame(['register'], $provider->calls);
        self::assertSame($provider, $app->register(RecordingProvider::class));
        self::assertSame($provider, $app->getProvider(RecordingProvider::class));
        self::assertNotSame($provider, $app->register(RecordingProvider::class, force: true));
    }

    public function testRegistersTheBindingsAndSingletonsOfTheProvider(): void
    {
        $app = $this->createApplication();
        $app->register(RecordingProvider::class);

        self::assertNotSame($app->make('recording.binding'), $app->make('recording.binding'));
        self::assertSame($app->make('recording.singleton'), $app->make('recording.singleton'));
        self::assertSame($app->make(\ArrayObject::class), $app->make(\ArrayObject::class));
    }

    public function testBootsTheProvidersOnce(): void
    {
        $app = $this->createApplication();
        $provider = new RecordingProvider($app);
        $app->register($provider);

        $app->boot();
        $app->boot();

        self::assertSame(['register', 'boot'], $provider->calls);
    }

    public function testThePathsAreNullUntilTheyAreSet(): void
    {
        $app = $this->createApplication();

        self::assertNull($app->configPath());
        self::assertNull($app->bootstrapPath());
        self::assertNull($app->getBootstrapProvidersPath());

        $app->useConfigPath('/project/config/')->useBootstrapPath('/project/bootstrap');

        self::assertSame('/project/config', $app->configPath());
        self::assertSame('/project/config/app.php', $app->configPath('/app.php'));
        self::assertSame('/project/bootstrap/providers.php', $app->getBootstrapProvidersPath());
    }

    public function testTheEnvironmentComesFromTheConfiguration(): void
    {
        $app = $this->createApplication(['app' => ['env' => fn (): string => 'production']]);

        self::assertTrue($app->isProduction());
        self::assertTrue($app->isEnvironment('production'));
        self::assertFalse($this->createApplication(['app' => ['env' => 'staging']])->isProduction());
    }

    public function testTheBootstrappersMustDefineABootstrapMethod(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('must define a bootstrap() method');

        $this->createApplication()->bootstrapWith([\stdClass::class]);
    }
}
