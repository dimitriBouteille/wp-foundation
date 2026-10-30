<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Cache\Clearer;

use Psr\Cache\CacheItemPoolInterface;

final readonly class PoolCacheClearer implements CacheClearerInterface
{
    public function __construct(
        private CacheItemPoolInterface $pool,
        private string $name = 'Cache pool',
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function clear(): void
    {
        if (!$this->pool->clear()) {
            throw new \RuntimeException('Unable to clear the cache pool.');
        }
    }
}
