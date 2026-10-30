<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Facades;

use Dbout\WpFoundation\View\AssetServiceProvider;
use Illuminate\Support\Facades\Facade;

/**
 * @method static string|null resolveUrl(string $fileKey)
 * @method static string|null resolvePath(string $fileKey)
 * @method static string|null getContent(string $fileKey)
 *
 * @see AssetServiceProvider
 */
class Asset extends Facade
{
    /**
     * @inheritDoc
     */
    protected static function getFacadeAccessor(): string
    {
        return 'asset';
    }
}
