<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Filesystem;

/**
 * Directory resolved by {@see DirectoryList}, the framework ones are in {@see Directory}, the project can add its own enum.
 */
interface DirectoryInterface extends \UnitEnum
{
    /**
     * Returns the default path of this directory, see {@see DirectoryList::getPath()} for the resolved path.
     *
     * @return string The default path relative to the application root
     */
    public function getPath(): string;
}
