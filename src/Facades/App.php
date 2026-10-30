<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Facades;

use Dbout\WpFoundation\Application;
use Dbout\WpFoundation\Filesystem\DirectoryList;
use Illuminate\Support\Facades\Facade;

/**
 * @method static bool isProduction()
 * @method static bool isEnvironment(string $code)
 * @method static DirectoryList getDir()
 *
 * @see Application
 */
class App extends Facade
{
    /**
     * @inheritDoc
     */
    protected static function getFacadeAccessor(): string
    {
        return 'app';
    }
}
