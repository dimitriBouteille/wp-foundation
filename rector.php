<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

use Rector\Php81\Rector\Array_\ArrayToFirstClassCallableRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/config',
        __DIR__ . '/tests',
    ])
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        earlyReturn: true,
    )
    ->withPhpSets(php83: true)
    ->withSkip([
        // The WordPress hooks of the package must stay removable with remove_action() / remove_filter()
        ArrayToFirstClassCallableRector::class,
        __DIR__ . '/tests/Fixtures',
    ]);
