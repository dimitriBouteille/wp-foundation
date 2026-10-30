<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation;

use Dbout\WpFoundation\Cache\Adapter\TagAwareAdapter;
use Dbout\WpFoundation\Config\Repository;
use Dbout\WpFoundation\Configuration\ApplicationBuilder;
use Dbout\WpFoundation\Filesystem\Directory;
use Dbout\WpFoundation\Filesystem\DirectoryList;
use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Facade;
use Throwable;

/**
 * Service container of the application, usually created by a mu-plugin:
 *
 * ```php
 * Application::configure($basePath)
 *     ->withPaths(config: $basePath . '/config', bootstrap: $basePath . '/bootstrap')
 *     ->create();
 * ```
 *
 * The instance is the global container ({@see app()}) and the application of the facades.
 * {@see ApplicationBuilder::create()} registers the base services (`dir`, `config`, `cache`, `events`, `log`),
 * registers the service providers, then boots them.
 *
 * A service provider is a plain class built with the application. It can define `register()` (bindings),
 * `boot()` (called once all the providers are registered) and the `$bindings` / `$singletons` properties.
 */
final class Application extends Container
{
    /**
     * @var array<class-string, object>
     */
    protected array $serviceProviders = [];

    /**
     * @var array<class-string, bool>
     */
    protected array $loadedProviders = [];

    protected string $basePath;

    protected ?string $configDirectory = null;

    protected ?string $bootstrapDirectory = null;

    protected bool $booted = false;

    /**
     * @param string $basePath Absolute path of the project root (the directories, e.g. `var`, are relative to it)
     */
    public function __construct(
        string $basePath,
    ) {
        $this->basePath = rtrim($basePath, '\/');

        $this->registerBaseBindings();
    }

    /**
     * Called by the Illuminate components (e.g. {@see \Illuminate\Log\LogManager}), the project has no test environment.
     */
    public function runningUnitTests(): bool
    {
        return false;
    }

    /**
     * Create the application and return its builder, the application is ready once {@see ApplicationBuilder::create()} is called.
     *
     * @param string $basePath Absolute path of the project root
     * @throws Throwable
     */
    public static function configure(string $basePath): ApplicationBuilder
    {
        return new ApplicationBuilder(new self($basePath));
    }

    /**
     * Absolute path of the project root, without trailing slash.
     */
    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * Whether the `app.env` config equals the given environment (e.g. "production", "staging", "development").
     *
     * @throws Throwable
     */
    public function isEnvironment(string $code): bool
    {
        return $this->getConfig()->get('app.env') === $code;
    }

    /**
     * In production, the cache (routes, hooks, Twig, ...) is enabled.
     */
    public function isProduction(): bool
    {
        return $this->isEnvironment('production');
    }

    /**
     * Register a service provider, once: the provider already registered is returned unless `$force` is true.
     *
     * @param object|class-string $provider Provider instance or class, the class is built with the application
     * @return object The registered provider
     */
    public function register($provider, bool $force = false): mixed
    {
        if (($registered = $this->getProvider($provider)) && !$force) {
            return $registered;
        }

        // If the given "provider" is a string, we will resolve it, passing in the
        // application instance automatically for the developer. This is simply
        // a more convenient way of specifying your service provider classes.
        if (is_string($provider)) {
            $provider = $this->resolveProvider($provider);
        }

        if (method_exists($provider, 'register')) {
            $provider->register();
        }

        // If there are bindings / singletons set as properties on the provider we
        // will spin through them and register them with the application, which
        // serves as a convenience layer while registering a lot of bindings.
        if (property_exists($provider, 'bindings')) {
            foreach ($provider->bindings as $key => $value) {
                $this->bind($key, $value);
            }
        }

        if (property_exists($provider, 'singletons')) {
            foreach ($provider->singletons as $key => $value) {
                $key = is_int($key) ? $value : $key;

                $this->singleton($key, $value);
            }
        }

        $this->markAsRegistered($provider);

        return $provider;
    }

    /**
     * @param object|class-string $provider
     * @return object|null The registered instance of the provider class, null if it is not registered
     */
    public function getProvider(mixed $provider): mixed
    {
        $name = is_string($provider) ? $provider : $provider::class;

        return $this->serviceProviders[$name] ?? null;
    }

    /**
     * @param class-string $provider
     */
    public function resolveProvider($provider): object
    {
        return new $provider($this);
    }

    /**
     * Boot the registered service providers, once all of them are registered.
     */
    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        foreach ($this->serviceProviders as $provider) {
            if (method_exists($provider, 'boot')) {
                $provider->boot();
            }
        }

        $this->booted = true;
    }

    /**
     * Set the absolute path of the application configuration directory.
     */
    public function useConfigPath(string $path): self
    {
        $this->configDirectory = rtrim($path, '\/');

        return $this;
    }

    /**
     * Set the absolute path of the bootstrap directory.
     */
    public function useBootstrapPath(string $path): self
    {
        $this->bootstrapDirectory = rtrim($path, '\/');

        return $this;
    }

    /**
     * Get the path to the service provider list in the bootstrap directory, null when the bootstrap directory is not set.
     */
    public function getBootstrapProvidersPath(): ?string
    {
        return $this->bootstrapPath('providers.php');
    }

    /**
     * Get the path to the bootstrap directory, null when it is not set (see {@see ApplicationBuilder::withPaths()}).
     */
    public function bootstrapPath(string $path = ''): ?string
    {
        return $this->bootstrapDirectory !== null ? $this->joinPaths($this->bootstrapDirectory, $path) : null;
    }

    /**
     * Get the path to the application configuration files, null when it is not set (see {@see ApplicationBuilder::withPaths()}).
     */
    public function configPath(string $path = ''): ?string
    {
        return $this->configDirectory !== null ? $this->joinPaths($this->configDirectory, $path) : null;
    }

    /**
     * Paths of the project directories, see {@see Directory}.
     *
     * @throws Throwable
     */
    public function getDir(): DirectoryList
    {
        /** @var DirectoryList $dir */
        $dir = $this->get('dir');

        return $dir;
    }

    /**
     * Application cache (`var/cache/data`), {@see TagAwareAdapter::get()} only caches in production.
     *
     * @throws Throwable
     */
    public function getCache(): TagAwareAdapter
    {
        /** @var TagAwareAdapter $cache */
        $cache = $this->get('cache');

        return $cache;
    }

    /**
     * Configuration of the package (`config` directory) merged with the one of the project, see {@see Bootstrap\LoadConfiguration}.
     *
     * @throws Throwable
     */
    public function getConfig(): Repository
    {
        /** @var Repository $config */
        $config = $this->get('config');

        return $config;
    }

    /**
     * Run the given array of bootstrap classes, built through the container, each one receiving the application.
     *
     * @param class-string[] $bootstrappers
     * @throws BindingResolutionException
     */
    public function bootstrapWith(array $bootstrappers): void
    {
        foreach ($bootstrappers as $bootstrapper) {
            $instance = $this->make($bootstrapper);
            if (!method_exists($instance, 'bootstrap')) {
                throw new \LogicException(sprintf('The bootstrapper %s must define a bootstrap() method.', $bootstrapper));
            }

            $instance->bootstrap($this);
        }
    }

    protected function registerBaseBindings(): self
    {
        self::setInstance($this);

        $this->instance('app', $this);
        $this->instance(self::class, $this);
        $this->instance(Container::class, $this);

        // @phpstan-ignore-next-line
        Facade::setFacadeApplication($this);

        return $this;
    }

    protected function markAsRegistered(object $provider): void
    {
        $class = $provider::class;
        $this->serviceProviders[$class] = $provider;
        $this->loadedProviders[$class] = true;
    }

    private function joinPaths(string $directory, string $path): string
    {
        if ($path === '') {
            return $directory;
        }

        return rtrim($directory, '\/') . DIRECTORY_SEPARATOR . ltrim($path, '\/');
    }
}
