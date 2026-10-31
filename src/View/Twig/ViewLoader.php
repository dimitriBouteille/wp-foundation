<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\View\Twig;

use Illuminate\Support\Str;
use Psr\Cache\CacheItemPoolInterface;

class ViewLoader
{
    private const string CACHE_KEY = 'app_twig_module_paths';

    /**
     * @param string[] $modules Glob patterns of the module directories.
     */
    public function __construct(
        protected array $modules,
        protected ?CacheItemPoolInterface $cache,
    ) {}

    /**
     * The paths are cached by modules config: a new module directory matching an existing pattern
     * is found once the cache is cleared (`wp app:cache:clear`).
     *
     * @throws \Psr\Cache\InvalidArgumentException
     * @return array<array{string, string}> List of [namespace, path] in declaration order.
     */
    public function loadPathsWithNamespaces(): array
    {
        $cache = $this->cache;
        if (!$cache instanceof CacheItemPoolInterface) {
            return $this->findPaths();
        }

        // The key depends on the modules, so that a change of the `twig.modules` config is seen without clearing the cache
        $cacheData = $cache->getItem(self::CACHE_KEY . '_' . hash('xxh128', serialize($this->modules)));
        /** @var array<array{string, string}>|null $paths */
        $paths = $cacheData->isHit() ? $cacheData->get() : null;
        if (is_array($paths)) {
            return $paths;
        }

        $paths = $this->findPaths();
        $cacheData->set($paths);
        $cache->save($cacheData);

        return $paths;
    }

    /**
     * @return array<array{string, string}> List of [namespace, path] in declaration order.
     */
    protected function findPaths(): array
    {
        $paths = [];
        foreach ($this->modules as $pattern) {
            $directories = glob($pattern, GLOB_ONLYDIR);
            if (!is_array($directories)) {
                continue;
            }

            foreach ($directories as $directory) {
                $viewsDirectory = $directory . DIRECTORY_SEPARATOR . 'views';
                if (!is_dir($viewsDirectory)) {
                    continue;
                }

                $paths[] = [Str::camel(basename($directory)), $viewsDirectory];
            }
        }

        return $paths;
    }
}
