<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static mixed get(string $key, callable $callback, float $beta = null, array<mixed> &$metadata = null)
 * @method static bool invalidateTags(string[] $tags)
 * @method static bool delete(string $key)
 * @method static bool prune()
 * @method static bool clear()
 */
class Cache extends Facade
{
    final public const int ONE_HOUR = 3600;

    final public const int TWELVE_HOURS = 43200;

    final public const int ONE_DAY = 86400;

    final public const int ONE_WEEK = 604800;

    /**
     * @inheritDoc
     */
    protected static function getFacadeAccessor(): string
    {
        return 'cache';
    }
}
