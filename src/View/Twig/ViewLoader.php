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
    private const CACHE_KEY = 'app_twig_module_paths';

    /**
     * @param string[] $modules Glob patterns of the module directories.
     */
    public function __construct(
        protected array $modules,
        protected ?CacheItemPoolInterface $cache,
    ) {}

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     * @return array<array{string, string}> List of [namespace, path] in declaration order.
     */
    public function loadPathsWithNamespaces(): array
    {
        $cache = $this->cache;
        if (!$cache instanceof CacheItemPoolInterface) {
            return $this->findPaths();
        }

        $cacheData = $cache->getItem(self::CACHE_KEY);
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
