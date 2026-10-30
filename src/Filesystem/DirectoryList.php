<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Filesystem;

/**
 * Provides centralized filesystem path management for the application.
 *
 * This readonly class handles path resolution relative to a root directory,
 * normalizing path separators for cross-platform compatibility.
 * Works in conjunction with the Directory enum to provide consistent
 * path resolution across the codebase.
 *
 * A directory can be moved with a custom absolute path, the other directories keep their default path
 * (i.e. a custom {@see Directory::Var} does not move {@see Directory::Cache}).
 */
readonly class DirectoryList
{
    /**
     * @var string The normalized root directory path
     */
    private string $root;

    /**
     * @var array<string, string> Custom absolute paths, by directory key (see {@see key()})
     */
    private array $paths;

    /**
     * @param string $root The absolute path to the application root directory
     * @param array<array{DirectoryInterface, string}> $paths Custom absolute paths of the directories, i.e. `[[Directory::Cache, '/tmp/cache']]`
     */
    public function __construct(string $root, array $paths = [])
    {
        $this->root = $this->filterPath($root);

        $customPaths = [];
        foreach ($paths as [$directory, $path]) {
            $customPaths[$this->key($directory)] = $this->filterPath($path);
        }

        $this->paths = $customPaths;
    }

    /**
     * Returns the application root directory path.
     *
     * @return string The normalized root path
     */
    public function getRoot(): string
    {
        return $this->root;
    }

    /**
     * Resolves the absolute path for a given directory, optionally appending a sub-path.
     *
     * @param DirectoryInterface $directory The directory enum case to resolve
     * @param string|null $path Optional relative path to append to the directory
     * @return string The full absolute path
     *
     * @example Get a directory path
     * ```php
     * $directoryList->getPath(Directory::Cache);
     * // Returns: "/var/www/myapp/var/cache/"
     * ```
     *
     * @example Get a file path within a directory
     * ```php
     * $directoryList->getPath(Directory::Log, 'app.log');
     * // Returns: "/var/www/myapp/var/logs/app.log"
     * ```
     */
    public function getPath(DirectoryInterface $directory, ?string $path = null): string
    {
        $root = $this->paths[$this->key($directory)] ?? $this->prependRoot($directory->getPath());

        return rtrim($root, '/') . DIRECTORY_SEPARATOR . ltrim((string) $path, '/');
    }

    private function key(DirectoryInterface $directory): string
    {
        return $directory::class . '::' . $directory->name;
    }

    /**
     * Normalizes path separators by converting backslashes to forward slashes.
     *
     * @param string $path The path to normalize
     * @return string The normalized path
     */
    private function filterPath($path): string
    {
        return str_replace('\\', '/', $path);
    }

    /**
     * Prepends the root directory to a given path.
     *
     * @param string $path The relative path to prepend root to
     * @return string The path with root prepended
     */
    private function prependRoot(string $path): string
    {
        $root = $this->getRoot();

        return $root . ($root && $path ? '/' : '') . $path;
    }
}
