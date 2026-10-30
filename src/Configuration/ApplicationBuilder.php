<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Configuration;

use Carbon\Carbon;
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
use Dbout\WpFoundation\Routing\RouterServiceProvider;
use Dbout\WpFoundation\Security\SecurityServiceProvider;
use Dbout\WpFoundation\Translation\TranslationServiceProvider;
use Dbout\WpFoundation\View\AssetServiceProvider;
use Dbout\WpFoundation\View\TwigServiceProvider;
use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Log\LogManager;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

final class ApplicationBuilder
{
    private const array DEFAULT_PROVIDERS = [
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
     * Set a custom absolute path for a directory (i.e. `Directory::Cache`), instead of its default path relative to the base path.
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
     * Load the configuration, register the service providers (they can add their own paths to the configuration),
     * then boot them.
     *
     * @throws \Throwable
     */
    public function create(): Application
    {
        $this->registerDefaultBindings();
        $this->app->bootstrapWith(self::DEFAULT_BOOTSTRAPPERS);
        $this->configureDate();
        $this->registerProviders();
        $this->app->boot();

        return $this->app;
    }

    /**
     * @throws \Throwable
     */
    private function registerProviders(): void
    {
        $providers = $this->providers;
        $bootstrapFile = $this->app->getBootstrapProvidersPath();
        if ($bootstrapFile !== null && file_exists($bootstrapFile)) {
            $providers = array_merge($providers, require $bootstrapFile);
        }

        foreach (array_merge(self::DEFAULT_PROVIDERS, $providers) as $provider) {
            $this->app->register($provider);
        }
    }

    private function registerDefaultBindings(): void
    {
        $this->app->singleton('dir', fn (Application $app) => new DirectoryList($app->getBasePath(), $this->directories));
        $this->app->singleton('events', fn ($app) => new Dispatcher($app));
        $this->app->singleton('log', fn ($app) => new LogManager($app));

        $this->app->singleton('cache', function (Container $app) {
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

    private function configureDate(): void
    {
        $config = $this->app->getConfig();
        $locale = $config->get('app.locale');
        if (is_string($locale)) {
            Carbon::setLocale($locale);
        }

        $translations = $config->get('app.date.translations', []);
        if (is_array($translations) && $translations !== []) {
            // @phpstan-ignore-next-line
            Carbon::getTranslator()->setTranslations($translations);
        }
    }
}
