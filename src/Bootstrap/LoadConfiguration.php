<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Bootstrap;

use Dbout\WpFoundation\Application;
use Dbout\WpFoundation\Config\Repository;
use Illuminate\Contracts\Config\Repository as RepositoryContract;
use Illuminate\Support\Collection;

final class LoadConfiguration
{
    /**
     * @throws \Throwable
     */
    public function bootstrap(Application $app): void
    {
        $items = [];

        $app->instance('config', $config = new Repository($items));
        $this->loadConfigurationFiles($app, $config);
    }

    /**
     * Load the configuration items from all the files.
     *
     * @throws \Throwable
     */
    protected function loadConfigurationFiles(Application $app, RepositoryContract $repository): void
    {
        $files = $this->getConfigurationFiles($app);

        $base = $this->getBaseConfiguration();
        foreach ((new Collection($base))->diffKeys($files) as $name => $config) {
            $repository->set($name, $config);
        }

        foreach ($files as $name => $path) {
            $this->loadConfigurationFile($repository, $name, $path, $base[$name] ?? null);
        }
    }

    /**
     * @throws \Throwable
     * @return array<string, string>
     */
    protected function getConfigurationFiles(Application $app): array
    {
        $files = [];
        $configPath = $app->configPath();
        $configPath = $configPath !== null ? realpath($configPath) : false;

        if (!$configPath) {
            return [];
        }

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($configPath, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo || !$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $path = (string) $file->getRealPath();
            $files[$this->getNestedDirectory($file, $configPath) . $file->getBasename('.php')] = $path;
        }

        ksort($files, SORT_NATURAL);

        return $files;
    }

    protected function getNestedDirectory(\SplFileInfo $file, string $configPath): string
    {
        $directory = $file->getPath();

        if ($nested = trim(str_replace($configPath, '', $directory), DIRECTORY_SEPARATOR)) {
            $nested = str_replace(DIRECTORY_SEPARATOR, '.', $nested) . '.';
        }

        return $nested;
    }

    /**
     * @param mixed $base The framework configuration with the same name, if any
     */
    protected function loadConfigurationFile(RepositoryContract $repository, string $name, string $path, mixed $base): void
    {
        $config = (fn () => require $path)();
        if (!is_array($config)) {
            throw new \UnexpectedValueException(sprintf('The configuration file %s must return an array.', $path));
        }

        if (is_array($base)) {
            $merged = array_merge($base, $config);
            foreach ($this->mergeableOptions($name) as $option) {
                $baseOption = $base[$option] ?? [];
                $value = $config[$option] ?? null;
                if (is_array($baseOption) && is_array($value)) {
                    $merged[$option] = array_merge($baseOption, $value);
                }
            }

            $config = $merged;
        }

        $repository->set($name, $config);
    }

    /**
     * Get the options within the configuration file that should be merged again.
     *
     * @return string[]
     */
    protected function mergeableOptions(string $name): array
    {
        return [
            'asset' => ['areas'],
            'logging' => ['channels'],
            'router' => ['namespaces', 'customPaths'],
            'twig' => ['extensions', 'modules'],
            'console' => ['namespaces'],
            'security' => ['headers', 'restApi'],
        ][$name] ?? [];
    }

    /**
     * Get the base configuration files.
     *
     * @return array<string, mixed>
     */
    protected function getBaseConfiguration(): array
    {
        $config = [];

        foreach (glob(dirname(__DIR__, 2) . '/config/*.php') ?: [] as $path) {
            $config[basename($path, '.php')] = require $path;
        }

        return $config;
    }
}
