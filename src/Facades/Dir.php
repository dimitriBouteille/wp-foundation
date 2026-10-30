<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Facades;

use Dbout\WpFoundation\Filesystem\DirectoryInterface;
use Dbout\WpFoundation\Filesystem\DirectoryList;
use Illuminate\Support\Facades\Facade;

/**
 * @method static string getPath(DirectoryInterface $directory, string $path = null)
 *
 * @see DirectoryList
 */
class Dir extends Facade
{
    /**
     * @inheritDoc
     */
    protected static function getFacadeAccessor(): string
    {
        return 'dir';
    }
}
