<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Tests;

use Brain\Monkey;
use Dbout\WpFoundation\Application;
use Dbout\WpFoundation\Config\Repository;
use Dbout\WpFoundation\Filesystem\DirectoryList;
use Dbout\WpFoundation\Filesystem\Filesystem;
use Dbout\WpFoundation\Tests\Doubles\Cli;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var string[]
     */
    private array $temporaryDirectories = [];

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        Cli::reset();
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
        Container::setInstance();

        foreach ($this->temporaryDirectories as $directory) {
            (new Filesystem())->deleteDirectory($directory);
        }

        parent::tearDown();
    }

    protected static function fixturePath(string $path = ''): string
    {
        return rtrim(__DIR__ . '/Fixtures/' . ltrim($path, '/'), '/');
    }

    protected function createTemporaryDirectory(): string
    {
        $directory = sys_get_temp_dir() . '/wp-foundation-' . bin2hex(random_bytes(6));
        mkdir($directory, 0o777, true);
        $this->temporaryDirectories[] = $directory;

        return $directory;
    }

    /**
     * Application with the given configuration, without the service providers.
     *
     * @param array<string, mixed> $config
     */
    protected function createApplication(array $config = [], ?string $basePath = null): Application
    {
        $app = new Application($basePath ?? $this->createTemporaryDirectory());
        $app->instance('config', new Repository($config));
        $app->instance('dir', new DirectoryList($app->getBasePath()));

        return $app;
    }
}
