<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\View;

use Dbout\WpFoundation\Application;
use Dbout\WpFoundation\Cache\Clearer\CacheClearerInterface;
use Dbout\WpFoundation\Cache\Clearer\DirectoryCacheClearer;
use Dbout\WpFoundation\Filesystem\Directory;
use Dbout\WpFoundation\View\Twig\ViewLoader;
use Twig\Environment;
use Twig\Extension\ExtensionInterface;
use Twig\Loader\FilesystemLoader;

/**
 * Service provider responsible for registering view-related services into the application container.
 *
 * This provider configures the Twig templating engine for the framework.
 * It automatically discovers template directories from modules and registers them with namespaced paths.
 *
 * Registered services:
 * - `twig`: The Twig Environment instance configured with module template paths and extensions.
 * - {@see ViewRendererInterface}: renders the controllers templates with this Twig Environment (can be replaced by the project).
 * - `twig.cache_clearer`: deletes the compiled templates, run by the `app:cache:clear` command.
 *
 * Template namespaces are derived from module directory names in camelCase format.
 * For example, a module at `src/Modules/UserAccount/views/` would be accessible as `@userAccount/template.twig`.
 *
 * Configuration:
 * - `twig.extensions`: Array of Twig extension class names to register.
 * - `twig.modules`: Glob patterns of the module directories, a later module overrides the templates of an earlier one sharing its namespace.
 *
 * @see ViewLoader For template path discovery logic.
 */
final readonly class TwigServiceProvider
{
    /**
     * @param Application $app The framework application instance.
     */
    public function __construct(
        private Application $app,
    ) {}

    /**
     * Registers the view services into the application container.
     */
    public function register(): void
    {
        $this->app->singleton('twig', function () {
            $modules = $this->app->getConfig()->get('twig.modules', []);
            $viewLoader = new ViewLoader(
                modules: is_array($modules) ? array_filter($modules, 'is_string') : [],
                cache: $this->app->isProduction() ? $this->app->getCache() : null,
            );

            $loader = new FilesystemLoader();
            foreach ($viewLoader->loadPathsWithNamespaces() as [$namespace, $path]) {
                if (is_dir($path)) {
                    $loader->prependPath($path, $namespace);
                }
            }

            $twig = new Environment($loader, [
                'strict_variables' => true,
                'debug' => !$this->app->isProduction(),
                'cache' => $this->app->isProduction() ? $this->getCachePath() : false,
            ]);

            $this->loadExtensions($twig);

            return $twig;
        });

        $this->app->singleton(ViewRendererInterface::class, function (): ViewRendererInterface {
            /** @var Environment $twig */
            $twig = $this->app->get('twig');

            return new TwigViewRenderer($twig);
        });

        $this->app->bind('twig.cache_clearer', fn (): DirectoryCacheClearer => new DirectoryCacheClearer('Twig templates', $this->getCachePath()));
        $this->app->tag('twig.cache_clearer', CacheClearerInterface::TAG);
    }

    /**
     * @throws \Throwable
     */
    private function getCachePath(): string
    {
        return $this->app->getDir()->getPath(Directory::Cache, 'twig');
    }

    /**
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \Throwable
     */
    private function loadExtensions(Environment &$twig): void
    {
        $extensions = $this->app->getConfig()->get('twig.extensions', []);
        if (!is_array($extensions)) {
            return;
        }

        foreach ($extensions as $extension) {
            $instance = is_string($extension) ? $this->app->make($extension) : $extension;
            if (!$instance instanceof ExtensionInterface) {
                throw new \Exception(sprintf(
                    'Twig extension "%s" must implement %s',
                    is_string($extension) ? $extension : get_debug_type($extension),
                    ExtensionInterface::class,
                ));
            }

            $twig->addExtension($instance);
        }
    }
}
