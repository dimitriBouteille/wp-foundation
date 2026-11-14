<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Module;

/**
 * A module groups the code of a feature (controllers, commands, views, providers, ...) that a site can enable or not,
 * by listing it in `bootstrap/modules.php` or with {@see \Dbout\WpFoundation\Configuration\ApplicationBuilder::withModules()}.
 */
interface ModuleInterface
{
    /**
     * Modules that must be enabled with this one.
     *
     * @return list<class-string<ModuleInterface>>
     */
    public function getDependencies(): array;

    /**
     * Service providers registered when the module is enabled.
     *
     * @return list<object|class-string>
     */
    public function getProviders(): array;

    /**
     * Values added to the lists of the configuration, keyed by option (e.g. `router.namespaces`, `twig.modules`).
     *
     * @return array<string, list<mixed>>
     */
    public function getConfiguration(): array;
}
