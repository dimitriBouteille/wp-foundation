<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Configuration;

use Dbout\WpFoundation\Application;
use Dbout\WpFoundation\Bootstrap\LoadConfiguration;
use Dbout\WpFoundation\Cache\Adapter\TagAwareAdapter;
use Dbout\WpFoundation\Cache\Clearer\CacheClearerInterface;
use Dbout\WpFoundation\Cache\Clearer\PoolCacheClearer;
use Dbout\WpFoundation\Console\CommandServiceProvider;
use Dbout\WpFoundation\Filesystem\Directory;
use Dbout\WpFoundation\Filesystem\DirectoryInterface;
use Dbout\WpFoundation\Filesystem\DirectoryList;
use Dbout\WpFoundation\Image\ImageServiceProvider;
use Dbout\WpFoundation\Module\ModuleInterface;
use Dbout\WpFoundation\Module\ModuleList;
use Dbout\WpFoundation\Routing\RouterServiceProvider;
use Dbout\WpFoundation\Security\SecurityServiceProvider;
use Dbout\WpFoundation\Translation\DateServiceProvider;
use Dbout\WpFoundation\Translation\TranslationServiceProvider;
use Dbout\WpFoundation\View\AssetServiceProvider;
use Dbout\WpFoundation\View\TwigServiceProvider;
use Illuminate\Container\Container;
use Illuminate\Contracts\Log\ContextLogProcessor as ContextLogProcessorContract;
use Illuminate\Events\Dispatcher;
use Illuminate\Log\Context\ContextLogProcessor;
use Illuminate\Log\LogManager;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

final class ApplicationBuilder
{
    private const array DEFAULT_PROVIDERS = [
        DateServiceProvider::class,
        RouterServiceProvider::class,
        CommandServiceProvider::class,
        TwigServiceProvider::class,
        AssetServiceProvider::class,
        ImageServiceProvider::class,
        SecurityServiceProvider::class,
        TranslationServiceProvider::class,
    ];

    private const array DEFAULT_BOOTSTRAPPERS = [
        LoadConfiguration::class,
    ];

    /**
     * @var array<object|class-string>
     */
    private array $providers = [];

    /**
     * @var array<ModuleInterface|class-string<ModuleInterface>>
     */
    private array $modules = [];

    /**
     * @var array<array{DirectoryInterface, string}>
     */
    private array $directories = [];

    public function __construct(
        protected Application $app,
    ) {}

    /**
     * Set the absolute paths of the configuration and bootstrap directories, nothing is loaded from the project when a path is not set.
     *
     * @return $this
     */
    public function withPaths(?string $config = null, ?string $bootstrap = null): self
    {
        if ($config !== null) {
            $this->app->useConfigPath($config);
        }

        if ($bootstrap !== null) {
            $this->app->useBootstrapPath($bootstrap);
        }

        return $this;
    }

    /**
     * Set a custom absolute path for a directory (e.g. `Directory::Cache`), instead of its default path relative to the base path.
     *
     * @return $this
     */
    public function withDirectory(DirectoryInterface $directory, string $path): self
    {
        $this->directories[] = [$directory, $path];

        return $this;
    }

    /**
     * Add service providers, in addition to the default ones and to the bootstrap `providers.php` file.
     *
     * @param array<object|class-string> $providers
     * @return $this
     */
    public function withProviders(array $providers = []): self
    {
        $this->providers = array_merge($this->providers, $providers);

        return $this;
    }

    /**
     * Enable modules, in addition to the ones of the bootstrap `modules.php` file.
     *
     * @param array<ModuleInterface|class-string<ModuleInterface>> $modules
     * @return $this
     */
    public function withModules(array $modules = []): self
    {
        $this->modules = array_merge($this->modules, $modules);

        return $this;
    }

    /**
     * Load the configuration, add the configuration of the modules, register the service providers
     * (they can add their own paths to the configuration), then boot them.
     *
     * @throws \Throwable
     */
    public function create(): Application
    {
        $this->registerDefaultBindings();
        $this->app->bootstrapWith(self::DEFAULT_BOOTSTRAPPERS);
        $modules = $this->loadModules();
        $this->registerProviders($modules);
        $this->app->boot();

        return $this->app;
    }

    /**
     * @throws \Throwable
     */
    private function loadModules(): ModuleList
    {
        $modules = ModuleList::create(
            array_merge($this->modules, $this->requireBootstrapFile($this->app->getBootstrapModulesPath())),
            fn (string $class): object => $this->app->make($class),
        );

        $modules->applyConfiguration($this->app->getConfig());
        $this->app->instance(ModuleList::class, $modules);
        $this->app->alias(ModuleList::class, 'modules');

        return $modules;
    }

    /**
     * @throws \Throwable
     */
    private function registerProviders(ModuleList $modules): void
    {
        $providers = array_merge(
            self::DEFAULT_PROVIDERS,
            $this->providers,
            $this->requireBootstrapFile($this->app->getBootstrapProvidersPath()),
            $modules->getProviders(),
        );

        foreach ($providers as $provider) {
            if (!is_object($provider) && (!is_string($provider) || !class_exists($provider))) {
                throw new \UnexpectedValueException(sprintf('The service provider %s does not exist.', is_string($provider) ? $provider : get_debug_type($provider)));
            }

            $this->app->register($provider);
        }
    }

    /**
     * @return array<mixed>
     */
    private function requireBootstrapFile(?string $path): array
    {
        if ($path === null || !file_exists($path)) {
            return [];
        }

        $items = require $path;
        if (!is_array($items)) {
            throw new \UnexpectedValueException(sprintf('The bootstrap file %s must return an array.', $path));
        }

        return $items;
    }

    private function registerDefaultBindings(): void
    {
        $this->app->singleton('dir', fn (Application $app): DirectoryList => new DirectoryList($app->getBasePath(), $this->directories));
        $this->app->singleton('events', fn ($app): Dispatcher => new Dispatcher($app));
        $this->app->singleton('log', fn ($app): LogManager => new LogManager($app));

        // The log manager uses the context processor, bound by the ContextServiceProvider in Laravel
        $this->app->bind(ContextLogProcessorContract::class, fn (): ContextLogProcessor => new ContextLogProcessor());

        $this->app->singleton('cache', function (Container $app): TagAwareAdapter {
            /** @var DirectoryList $dir */
            $dir = $app->get('dir');

            return new TagAwareAdapter(
                itemsPool: new FilesystemAdapter(directory: $dir->getPath(Directory::Cache, 'data')),
                enabled: $this->app->isProduction(),
            );
        });

        $this->app->bind(PoolCacheClearer::class, fn (Application $app): PoolCacheClearer => new PoolCacheClearer($app->getCache()));
        $this->app->tag(PoolCacheClearer::class, CacheClearerInterface::TAG);
    }
}
