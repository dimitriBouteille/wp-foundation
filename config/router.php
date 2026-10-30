<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

/**
 * Router Configuration
 *
 * This configuration file defines the settings for the application router.
 *
 * The router automatically discovers and registers controller classes
 * based on the configured namespaces.
 */
return [
    /**
     * PSR-4 namespaces of the controller classes (i.e. `App\Controller\` or `App\*\Controller\`), the directories are resolved from the Composer autoload.
     */
    'namespaces' => [],

    'customPaths' => [
        'home' => fn () => get_home_url(),
    ],
];
