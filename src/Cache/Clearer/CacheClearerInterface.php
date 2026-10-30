<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Cache\Clearer;

/**
 * Clears a cache, run by the `app:cache:clear` command.
 *
 * The clearers are tagged in the container with {@see CacheClearerInterface::TAG}:
 *
 * ```php
 * $app->tag(MyCacheClearer::class, CacheClearerInterface::TAG);
 * ```
 */
interface CacheClearerInterface
{
    public const string TAG = 'cache.clearers';

    /**
     * Name displayed by the command (i.e. "Twig templates").
     */
    public function getName(): string;

    /**
     * @throws \Throwable When the cache cannot be cleared
     */
    public function clear(): void;
}
