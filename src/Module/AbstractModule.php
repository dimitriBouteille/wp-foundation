<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Module;

/**
 * Module whose configuration is found from the directories next to its class:
 *
 * - `Controller/`: added to `router.namespaces`;
 * - `Console/`: added to `console.namespaces`;
 * - `views/`: the module directory is added to `twig.modules` (Twig namespace named after the directory).
 *
 * Override {@see getConfiguration()} to add other options, merged with the parent ones.
 */
abstract class AbstractModule implements ModuleInterface
{
    private ?string $path = null;

    /**
     * @inheritDoc
     */
    public function getDependencies(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getProviders(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getConfiguration(): array
    {
        return array_filter([
            'router.namespaces' => $this->namespaceIfExists('Controller'),
            'console.namespaces' => $this->namespaceIfExists('Console'),
            'twig.modules' => $this->hasDirectory('views') ? [$this->getPath()] : [],
        ]);
    }

    /**
     * Absolute path of the directory of the module class, without trailing slash.
     */
    public function getPath(): string
    {
        return $this->path ??= dirname((string) (new \ReflectionClass($this))->getFileName());
    }

    /**
     * Namespace of the module class, without trailing backslash.
     */
    public function getNamespace(): string
    {
        return (new \ReflectionClass($this))->getNamespaceName();
    }

    protected function hasDirectory(string $directory): bool
    {
        return is_dir($this->getPath() . DIRECTORY_SEPARATOR . $directory);
    }

    /**
     * @return list<string> The namespace of the sub-directory (with a trailing backslash) when it exists
     */
    protected function namespaceIfExists(string $directory): array
    {
        if (!$this->hasDirectory($directory)) {
            return [];
        }

        return [$this->getNamespace() . '\\' . str_replace('/', '\\', trim($directory, '/')) . '\\'];
    }

    /**
     * @return list<string> The absolute path of the sub-directory when it exists
     */
    protected function pathIfExists(string $directory): array
    {
        return $this->hasDirectory($directory) ? [$this->getPath() . DIRECTORY_SEPARATOR . trim($directory, '/')] : [];
    }
}
