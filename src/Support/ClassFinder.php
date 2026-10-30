<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Support;

use Composer\Autoload\ClassLoader;

/**
 * Finds the concrete classes of a PSR-4 namespace (e.g. `App\Controller\`): the directories are resolved
 * from the Composer autoloaders and the class names are derived from the file paths.
 *
 * The namespace can contain `*` wildcards (e.g. `App\*\Controller\`), the matching directories are found with `glob()`.
 */
class ClassFinder
{
    /**
     * @var ClassLoader[]
     */
    protected array $classLoaders;

    /**
     * @param ClassLoader[]|null $classLoaders Defaults to all the registered Composer autoloaders (the plugins can register their own)
     */
    public function __construct(?array $classLoaders = null)
    {
        $this->classLoaders = $classLoaders ?? array_values(ClassLoader::getRegisteredLoaders());
    }

    /**
     * @throws \InvalidArgumentException When no directory is found for a namespace without wildcard
     * @return class-string[]
     */
    public function find(string $namespace): array
    {
        $namespace = trim($namespace, '\\') . '\\';
        $directories = [];
        foreach ($this->classLoaders as $classLoader) {
            $directories += $this->getDirectories($classLoader, $namespace);
        }

        if ($directories === [] && !str_contains($namespace, '*')) {
            throw new \InvalidArgumentException(sprintf('No directory found for the namespace "%s" in the Composer PSR-4 autoload.', $namespace));
        }

        $classes = [];
        ksort($directories);
        foreach ($directories as $directory => $directoryNamespace) {
            foreach ($this->findClasses($directoryNamespace, $directory) as $class) {
                if (class_exists($class) && !(new \ReflectionClass($class))->isAbstract()) {
                    $classes[] = $class;
                }
            }
        }

        return $classes;
    }

    /**
     * Directories matching the namespace, from the longest PSR-4 prefix matching its part before the first wildcard.
     *
     * @return array<string, string> The namespace of each directory, indexed by the directory path
     */
    protected function getDirectories(ClassLoader $classLoader, string $namespace): array
    {
        $fixedPart = strstr($namespace, '*', true);
        $fixedPart = $fixedPart === false ? $namespace : $fixedPart;

        $prefixes = $classLoader->getPrefixesPsr4();
        $longest = null;
        foreach (array_keys($prefixes) as $prefix) {
            if (str_starts_with($fixedPart, $prefix) && strlen($prefix) > strlen($longest ?? '')) {
                $longest = $prefix;
            }
        }

        if ($longest === null) {
            return [];
        }

        $subPath = str_replace('\\', DIRECTORY_SEPARATOR, substr($namespace, strlen($longest)));
        $directories = [];
        foreach ($prefixes[$longest] as $base) {
            $base = rtrim($base, '\/') . DIRECTORY_SEPARATOR;
            foreach (glob($base . $subPath, GLOB_ONLYDIR) ?: [] as $match) {
                $directory = realpath($match);
                if ($directory === false) {
                    continue;
                }

                $relative = trim(substr($match, strlen($base)), DIRECTORY_SEPARATOR);
                $directories[$directory] = $longest . ($relative !== '' ? str_replace(DIRECTORY_SEPARATOR, '\\', $relative) . '\\' : '');
            }
        }

        return $directories;
    }

    /**
     * @return string[]
     */
    protected function findClasses(string $namespace, string $directory): array
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
        );

        $classes = [];
        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo || !$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $relative = substr($file->getPathname(), strlen($directory) + 1, -4);
            $classes[] = $namespace . str_replace(DIRECTORY_SEPARATOR, '\\', $relative);
        }

        sort($classes);

        return $classes;
    }
}
