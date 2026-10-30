<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Console;

use Dbout\WpFoundation\Application;
use Dbout\WpFoundation\Console\Attribute\Command;
use Dbout\WpFoundation\Support\ClassFinder;

/**
 * Registers the WP-CLI commands: the classes with the {@see Command} attribute in the `console.namespaces`
 * (wildcards supported, see {@see ClassFinder}), built by the container. The commands are registered on `cli_init`, only fired by WP-CLI.
 */
readonly class CommandServiceProvider
{
    public function __construct(
        private Application $app,
    ) {}

    public function boot(): void
    {
        add_action('cli_init', fn () => $this->registerCommands());
    }

    /**
     * @throws \Throwable
     */
    private function registerCommands(): void
    {
        $namespaces = $this->app->getConfig()->get('console.namespaces', []);
        if (!is_array($namespaces)) {
            return;
        }

        $finder = new ClassFinder();
        foreach (array_filter($namespaces, is_string(...)) as $namespace) {
            foreach ($finder->find($namespace) as $class) {
                $attribute = (new \ReflectionClass($class))->getAttributes(Command::class)[0] ?? null;
                if ($attribute === null) {
                    continue;
                }

                $command = $attribute->newInstance();
                \WP_CLI::add_command($command->name, $this->app->make($class), array_filter([
                    'shortdesc' => $command->description,
                ]));
            }
        }
    }
}
