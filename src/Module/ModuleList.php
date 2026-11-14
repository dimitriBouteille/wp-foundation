<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Module;

use Dbout\WpFoundation\Module\Exception\ModuleException;
use Illuminate\Contracts\Config\Repository;

/**
 * Enabled modules, in the order of declaration (a module declared twice is kept once).
 *
 * The dependencies are checked, not sorted: a module can be declared before its dependencies, and two modules
 * can depend on each other.
 *
 * @implements \IteratorAggregate<class-string<ModuleInterface>, ModuleInterface>
 */
final class ModuleList implements \IteratorAggregate, \Countable
{
    /**
     * @var array<class-string<ModuleInterface>, ModuleInterface>
     */
    private array $modules = [];

    /**
     * @param iterable<ModuleInterface> $modules
     * @throws ModuleException When a dependency of a module is not enabled
     */
    public function __construct(iterable $modules = [])
    {
        foreach ($modules as $module) {
            $this->modules[$module::class] ??= $module;
        }

        foreach ($this->modules as $class => $module) {
            $missing = array_values(array_filter(
                $module->getDependencies(),
                fn (string $dependency): bool => !$this->has($dependency),
            ));

            if ($missing !== []) {
                throw ModuleException::missingDependencies($class, $missing);
            }
        }
    }

    /**
     * Build the modules given as class names with the factory.
     *
     * @param array<mixed> $modules Instances or class names
     * @param \Closure(class-string): object $factory
     * @throws ModuleException When a module does not implement {@see ModuleInterface} or a dependency is not enabled
     */
    public static function create(array $modules, \Closure $factory): self
    {
        $instances = [];
        foreach ($modules as $module) {
            if (is_string($module)) {
                if (!class_exists($module)) {
                    throw ModuleException::invalidModule($module);
                }

                $module = $factory($module);
            }

            if (!$module instanceof ModuleInterface) {
                throw ModuleException::invalidModule(get_debug_type($module));
            }

            $instances[] = $module;
        }

        return new self($instances);
    }

    /**
     * @param class-string $module
     */
    public function has(string $module): bool
    {
        return isset($this->modules[$module]);
    }

    /**
     * @template T of ModuleInterface
     * @param class-string<T> $module
     * @return T|null
     */
    public function get(string $module): ?ModuleInterface
    {
        /** @var T|null $instance */
        $instance = $this->modules[$module] ?? null;

        return $instance;
    }

    /**
     * @return array<class-string<ModuleInterface>, ModuleInterface>
     */
    public function all(): array
    {
        return $this->modules;
    }

    /**
     * Hash of the enabled modules, to add to the cache keys that depend on them.
     */
    public function getHash(): string
    {
        return hash('xxh128', implode(',', array_keys($this->modules)));
    }

    /**
     * Service providers of the modules, in the order of the modules.
     *
     * @return list<object|class-string>
     */
    public function getProviders(): array
    {
        $providers = [];
        foreach ($this->modules as $module) {
            array_push($providers, ...$module->getProviders());
        }

        return $providers;
    }

    /**
     * Add the configuration of the modules to the lists of the repository.
     */
    public function applyConfiguration(Repository $config): void
    {
        foreach ($this->modules as $module) {
            foreach ($module->getConfiguration() as $key => $values) {
                $current = $config->get($key, []);
                $config->set($key, array_merge(is_array($current) ? $current : [], $values));
            }
        }
    }

    /**
     * @return \ArrayIterator<class-string<ModuleInterface>, ModuleInterface>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->modules);
    }

    public function count(): int
    {
        return count($this->modules);
    }
}
